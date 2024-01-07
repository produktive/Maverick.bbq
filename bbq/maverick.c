#define STATE_START_PULSES      0
#define STATE_FIRST_BIT         1
#define STATE_DATA              2
#define PIN                     15
#define DBFILE					"/var/www/bbq/the.db"

#include <pigpio.h>
#include <stdio.h>
#include <stdlib.h>
#include <sqlite3.h>
#include <time.h>

unsigned int volatile start_pulse_counter=0,detection_state=0,last_interrupt_millis;
unsigned int data_array_index=0,data_array[13],shift_value=0,short_bit=0,add_1st_bit=1,current_byte=0;
unsigned int current_bit=1,bit_count=0,save_array[13],last_db_write,cookID,probe1_array[6],probe2_array[6];
unsigned int bit_ok,i,pin_state,firstRead=1,goodData,badReadCount,time_since_last;
int probe1=0,probe2=0,prevProbe1=0,prevProbe2=0,rc,current_micros,current_millis;
char *zErrMsg=0;
unsigned int shortBitTick,transmissionCount,nibbleShift,nibbleOne,nibbleTwo;
uint32_t toCheck,tsl_micros,last_interrupt_micros, checksum;

sqlite3 *db;

/*
apparent checksum calculation from:
 https://forums.adafruit.com/viewtopic.php?f=8&t=25414&sid=e1775df908194d56692c6ad9650fdfb2&start=15#p322178
*/
uint16_t shiftreg(uint16_t currentValue) {
    uint8_t msb = (currentValue >> 15) & 1;
    currentValue <<= 1;
    if (msb == 1) {
        // Toggle pattern for feedback bits
        // Toggle, if MSB is 1
        currentValue ^= 0x1021;
    }
    return currentValue;
}

//data = binary representation of nibbles 6 - 17
//e.g. xxxx:xxxx:xxxx:0010:1000:1010:0110:0101:0101:xxxx:xxxx:xxxx:xxxx
//  -> uint32_t data = 0x28a655
uint16_t calculate_checksum(uint32_t data) {
    uint16_t mask = 0x3331; //initial value of linear feedback shift register
    uint16_t csum = 0x0;
    int i = 0;
    for(i = 0; i < 24; ++i) {
        if((data >> i) & 0x01) {
           //data bit at current position is "1"
           //do XOR with mask
          csum ^= mask;
        }
        mask = shiftreg(mask);
    }
    return csum;
}

// make the quarternary convertion
unsigned int quart(unsigned int param) {
	param &= 0x0F;
	if (param==0x05)
		return(0);
        if (param==0x06)
                return(1);
        if (param==0x09)
                return(2);
        if (param==0x0A)
                return(3);
}

static int callback(void *data, int argc, char **argv, char **colName) {
        int i;
        for(i=0; i<argc; i++) {
        }
        return 0;
}

void outputData() {
        unsigned int i=0;
	int secs, mics;
	gpioTime(PI_TIME_RELATIVE,&secs,&mics);



	if (firstRead==0) {
		if (goodData==1) {
			badReadCount=0;
			prevProbe1=probe1;
			prevProbe2=probe2;
		}
	}

        if ((save_array[0] == 0xAA) &&
            (save_array[1] == 0x99) &&
            (save_array[2] == 0x95) &&
            (save_array[3] == 0x59)) {

		probe1 = probe2 = 0;
                probe2_array[0]= quart(save_array[8] & 0x0F);
                probe2_array[1]= quart(save_array[8] >> 4);
                probe2_array[2]= quart(save_array[7] & 0x0F);
                probe2_array[3]= quart(save_array[7] >> 4);
                probe2_array[4]= quart(save_array[6] & 0x0F);
                probe1_array[0]= quart(save_array[6] >> 4);
                probe1_array[1]= quart(save_array[5] & 0x0F);
                probe1_array[2]= quart(save_array[5] >> 4);
                probe1_array[3]= quart(save_array[4] & 0x0F);
                probe1_array[4]= quart(save_array[4] >> 4);

                for (i=0;i<=4;i++) {
			probe1 += probe1_array[i] * (1<<(2*i));
                        probe2 += probe2_array[i] * (1<<(2*i));
                }

                probe1 -= 532;
                probe1 = (((probe1 * 9)/5) + 32);

                probe2 -= 532;
                probe2 = (((probe2 * 9)/5) + 32);

		if (probe1>=858992500 || probe1<0) {
			probe1=0;
		}
		if (probe2>=858992500 || probe2<0) {
			probe2=0;
		}

		goodData=1;
		if (firstRead==0) {
			if ((probe1<(prevProbe1-20)) || (probe1>(prevProbe1+20))) {
				badReadCount++;
				if (badReadCount<3) {
					goodData=0;
					printf("Bad data #%d - Probe 1:%d\tPrevProbe 1:%d\t@%d\n",badReadCount,probe1,prevProbe1,secs);
				} else {
					goodData=1;
				}
			} else if ((probe2<(prevProbe2-20)) || (probe2>(prevProbe2+20))) {
				badReadCount++;
				if (badReadCount<3) {
					goodData=0;
					printf("Bad data #%d - Probe 2:%d\tPrevProbe 2:%d\t@%d\n",badReadCount,probe2,prevProbe2,secs);
				} else {
					goodData=1;
				}
			}
		}

		firstRead=0;

                printf("Probe 1:%d\tProbe 2:%d\t@%d\n",probe1,probe2,secs);

		char sql[100];
                time_t now = time(NULL);
		char buff[20];
                strftime(buff, 20, "%Y-%m-%d %H:%M:%S", localtime(&now));

		if (goodData==1) {
			if (last_db_write==0 || (secs-last_db_write>=10)) {
				snprintf(sql,100,"INSERT INTO readings (cookid,time,probe1,probe2) VALUES (%d,'%s',%d,%d);",cookID,buff,probe1,probe2);
				printf("%s\n",sql);
				rc=sqlite3_exec(db,sql,callback,0,&zErrMsg);
				if (rc!=SQLITE_OK) {
					printf("SQL error: %s\n",zErrMsg);
				} else {
					last_db_write=secs;
					system("/var/www/bbq/alerthandler.php");
				}
			}
		}
        }
}

void readPin (int gpio,int pin_state,uint32_t tick) {
	//current_micros=micros();
	//current_millis=millis();
	//pin_state=digitalRead(PIN);

	int secs,mics;
	gpioTime(PI_TIME_RELATIVE,&secs,&mics);

	bit_ok = 0;

        //get the time since last interrupt in milli and micro seconds
        tsl_micros=(tick-last_interrupt_micros);

        //store current interrupt time to calculate time since last (above)
        last_interrupt_micros=tick;

        //here we're attempting to detect the Maverick's preamble - 8x pulses of ~5ms each, spaced at ~250us
        if (detection_state == STATE_START_PULSES) {
                //if last interrupt was seen between 3ms and 7ms ago
		if (((tsl_micros>=4900 && tsl_micros<=5100) || (start_pulse_counter>6 && tsl_micros>=4700 && tsl_micros<=5100)) && pin_state==1) {
                        start_pulse_counter++;
			//we need to code to allow either 8 or 9 start pulses
			//depending on distance, this reciever seems to behave differently
			//in terms of picking up the pulses
                        //if (start_pulse_counter==9) {
                        if (start_pulse_counter==8) {
                                printf("Possible preamble detected @%d\n",tick);
                                start_pulse_counter=0;
                                detection_state=STATE_FIRST_BIT;
                        } else if (start_pulse_counter>0) {
				printf("*TRIGGER* Since last pulse: %dms (%dus), Time from start: %ds, Pulse count:%d \n",tsl_micros/1000,tsl_micros,secs, start_pulse_counter);
			}
                } else if (tsl_micros>400) {
			if (start_pulse_counter>=6) {
				if (transmissionCount==3) {
					transmissionCount=0;
				}
				transmissionCount++;
			}
			if (start_pulse_counter>4) {
				printf("*FAIL* Since last pulse: %dms (%dus), Time from start: %ds, Pulse count:%d,trancount: %d\n",tsl_micros/1000,tsl_micros,secs, start_pulse_counter,transmissionCount);
			}
                        start_pulse_counter=0;
                }
	}
        if (detection_state==STATE_FIRST_BIT && pin_state==1) {
                detection_state=STATE_DATA;
                current_bit=1;
                current_byte=0;
		nibbleOne=0;
		nibbleTwo=0;
                shift_value=0;
                data_array_index=0;
                bit_ok=0;
                short_bit=0;
                add_1st_bit = 1;
                bit_count = 1;
		if (transmissionCount==3) {
			transmissionCount=0;
		}
		transmissionCount++;
		toCheck=0;
		checksum=0;
                printf("Set first bit, going DATA@%d, trancount: %d\n",tick,transmissionCount);
        }

        else if (detection_state==STATE_DATA) {
		if (add_1st_bit==0 || (tsl_micros>=400 && tsl_micros<=580)) {
                //if ((tsl_micros > 150) && (tsl_micros < 330)) {
                if ((tsl_micros > 150) && (tsl_micros<=400)) {
                        if (short_bit == 0) {
		                //printf("Short bit flag @%d tsl %d\n",tick,tsl_micros);
				shortBitTick=tsl_micros;
                                short_bit = 1;
                        } else {
				shortBitTick=0;
                                bit_count++;
                                short_bit = 0;
                                bit_ok = 1;
                                current_bit=pin_state;
                        }
                }

                if ((tsl_micros>400) && (tsl_micros < 600)) {
                        if (short_bit == 1) {
                                //expected a short bit and something went wrong
                                //start over at getting preamble
                                detection_state = STATE_START_PULSES;
                                printf("!!!PATTERN FAILURE!!! @%d since last: %d on bit #%d byte #%d - short bit tick was %d\n",tick,tsl_micros,bit_count,data_array_index,shortBitTick);
				shortBitTick=0;
                                short_bit=0;

			} else {
	                        bit_count++;
                	        current_bit=pin_state;
                        	bit_ok = 1;
			}
                }

                if (bit_ok) {
                        if (add_1st_bit) {
                                //current_byte = 0x01;
				nibbleOne=0x01;
                                shift_value = 1;
				nibbleShift=1;
                                add_1st_bit = 0;
				//toCheck=1;
                        }

			if (shift_value<=3) {
	                        nibbleOne=(nibbleOne<<1)+current_bit;
			} else {
	                        nibbleTwo=(nibbleTwo<<1)+current_bit;
			}

			if (nibbleShift>=25 && nibbleShift<=48) {
				toCheck=(toCheck<<1)+current_bit;
				printf("toCheck is 0x%X at %d (bit is %d)\n",toCheck,nibbleShift,current_bit);
			}

			if (nibbleShift>=49 && nibbleShift<=72) {
				checksum=(checksum<<1)+current_bit;
				printf("checksum is 0x%X at %d (bit is %d)\n",checksum,nibbleShift,current_bit);
			}

                        //current_byte = (current_byte << 1) + current_bit;
			//printf("bit #%d is %d (%dus)\n",bit_count,current_bit,tsl_micros);
                        shift_value++;
			nibbleShift++;

			/*
			nibbles can only be these values - use this to recover invalid nibbles

			0x5 - 0101
			0x6 - 0110
			0x9 - 1001
			0xA - 1010

			1. Gather all 3 transmissions worth of nibbles/bits
			2. open new thread to validate checksum/record values
			3. if checksum is invalid for all three, try to recover:
				a. use known possible nibbles/bytes
				b. compare against recent recorded temps
			*/

			if (shift_value==4) {
				//printf("dis nibble be 0x%X d:%d\n",nibbleOne,nibbleOne);
				if (nibbleOne!=0x5 &&
				    nibbleOne!=0x6 &&
				    nibbleOne!=0x9 &&
				    nibbleOne!=0xA) {
					printf("bad nibble one value, resetting\n");
	                                detection_state = STATE_START_PULSES;
					shortBitTick=0;
                	                short_bit=0;
				}
			} else if (shift_value==8) {
				//printf("dis nibble be 0x%X d:%d\n",nibbleTwo,nibbleTwo);
				if (nibbleTwo!=0x5 &&
				    nibbleTwo!=0x6 &&
				    nibbleTwo!=0x9 &&
				    nibbleTwo!=0xA) {
					printf("bad nibble two value, resetting\n");
	                                detection_state = STATE_START_PULSES;
					shortBitTick=0;
                	                short_bit=0;
				}
			}

                        if (shift_value == 8) {
				current_byte=(nibbleOne<<4)+nibbleTwo;
				//printf("byte is 0x%X\n",current_byte);
				//printf("byte is %d\n",current_byte);
                                data_array[data_array_index++] = current_byte;
                                bit_count=0;
                                shift_value = 0;
                                current_byte = 0;
				nibbleOne=0;
				nibbleTwo=0;
                        }

                        //if (data_array_index==9) {
                        if (data_array_index==13) {
                                start_pulse_counter = 0;
				detection_state = STATE_START_PULSES;
				//toCheck=0;
				//checksum=0;
                                for (i=0;i<=12;i++) {
					if (i==0) {printf("Header: ");}
					else if (i==3) {printf("Startup: ");}
					else if (i==4) {printf("Temps: ");}
					else if (i==9) {printf("Checksum: ");}
					printf("0x%02X ",data_array[i]);
					if (i==2 || i==3 || i==8) {printf("\n");}
                                        save_array[i] = data_array[i];

					/*
					if (i>=3 && i<=8) {
						toCheck=(toCheck<<8)+data_array[i];
					}
					*/
                                }
				printf("\n");
				printf("toCheck: 0x%X\n",toCheck);
				//printf("decimal checksum: %d 0x%X\n",checksum,checksum);
				//printf("0x%X\n",2664021);
				//toCheck=2664021;
				//printf("toCheck: %d\n",toCheck);
				//printf("tallied: %d 0x%X\n",checksum,checksum);
				printf("Calculated Checksum: 0x%X\n",calculate_checksum(toCheck));
				printf("Calculated Checksum: %d\n",calculate_checksum(toCheck));
				fflush(stdout);
				outputData();
                        }
                        bit_ok = 0;
                }
		} else {
			printf("skipping in STATE_DATA at %ds (%dus)\n",secs,tsl_micros);
		}
        }
	fflush(stdout);
}

int main(int argc, char **argv)
{
	if (gpioInitialise()<0) {
	        printf("Failed to start gpio on BCM PIN %d\n",PIN);
		return 1;
	}
	printf("Starting on BCM PIN %d\n",PIN);

	if (gpioSetAlertFunc(PIN,readPin)>0) {
		printf("Failed to set alert on BCM PIN %d\n",PIN);
		return 1;
	}
	printf("Alert set on BCM PIN %d\n",PIN);


	rc=sqlite3_open(DBFILE,&db);
	if (rc!=SQLITE_OK) {
		printf("Can't open db: %s\n",sqlite3_errmsg(db));
	} else	{
		printf("db opened\n");
		//create new cook in cooks table
                time_t now = time(NULL);
		char buff[20];
                strftime(buff, 20, "%Y-%m-%d %H:%M:%S", localtime(&now));
		char sql[100];
		snprintf(sql,100,"INSERT INTO cooks (start) VALUES ('%s');",buff);
		rc=sqlite3_exec(db,sql,callback,0,&zErrMsg);
		if (rc!=SQLITE_OK) {
			printf("SQL error inserting into cooks: %s\n",zErrMsg);
		} else	{
			cookID=sqlite3_last_insert_rowid(db);
			printf("Cook ID is %d\n",cookID);

			//check if there's anything in the activecook table
			sqlite3_stmt *stmt;
			sqlite3_prepare_v2(db,"select cookid from activecook",-1,&stmt,NULL);
			int i;
			while (sqlite3_step(stmt) != SQLITE_DONE) {
				int num_cols = sqlite3_column_count(stmt);

				for (i = 0; i < num_cols; i++)
				{
				}
			}

			//set active cook ID in DB for use in HTML interface
			if (i!=0) {
				snprintf(sql,100,"UPDATE activecook SET cookid=%d;",cookID);
			} else {
				snprintf(sql,100,"INSERT INTO activecook (cookid) values (%d);",cookID);
			}
			rc=sqlite3_exec(db,sql,callback,0,&zErrMsg);
			if (rc!=SQLITE_OK) {
				printf("SQL error inserting/updating activecook: %s\n",zErrMsg);
			} else {
				printf("activecook set to %d successfully\n",cookID);
			}
		}

	}
        for (;;)
        {
		gpioDelay(60000);
        }
	sqlite3_close(db);
        return 0;
}
