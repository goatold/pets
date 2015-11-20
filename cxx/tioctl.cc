#include <sys/mount.h>
#include <stdio.h>
#include <ctype.h>
#include <unistd.h>
#include <linux/hdreg.h>
#include <string.h>
#include <stdlib.h>
#include <sys/ioctl.h>
#include <sys/types.h>
#include <sys/time.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <sys/shm.h>

#define TIMING_BUF_MB		2
#define TIMING_BUF_BYTES	(TIMING_BUF_MB * 1024 * 1024)
static int open_flags = O_RDONLY|O_NONBLOCK;


struct ata_smart_errorlog_error_struct {
  unsigned char reserved;
  unsigned char error_register;
  unsigned char sector_count;
  unsigned char sector_number;
  unsigned char cylinder_low;
  unsigned char cylinder_high;
  unsigned char drive_head;
  unsigned char status;
  unsigned char extended_error[19];
  unsigned char state;
  unsigned short timestamp;
} __attribute__ ((packed));

struct ata_smart_errorlog_command_struct {
  unsigned char devicecontrolreg;
  unsigned char featuresreg;
  unsigned char sector_count;
  unsigned char sector_number;
  unsigned char cylinder_low;
  unsigned char cylinder_high;
  unsigned char drive_head;
  unsigned char commandreg;
  unsigned int timestamp;
} __attribute__ ((packed));

struct ata_smart_errorlog_struct {
  struct ata_smart_errorlog_command_struct commands[5];
  struct ata_smart_errorlog_error_struct error_struct;
}  __attribute__ ((packed));

struct ata_smart_errorlog {
  unsigned char revnumber;
  unsigned char error_log_pointer;
  struct ata_smart_errorlog_struct errorlog_struct[5];
  unsigned short int ata_error_count;
  unsigned char reserved[57];
  unsigned char checksum;
} __attribute__ ((packed));


int isbigendian(){
  short i=0x0100;
  char *tmp=(char *)&i;
  return *tmp;
}

void swap2(char *location){
  char tmp=*location;
  *location=*(location+1);
  *(location+1)=tmp;
  return;
}

void swap4(char *location){
  char tmp=*location;
  *location=*(location+3);
  *(location+3)=tmp;
  swap2(location+1);
  return;
}

void swapbytes(char *out, const char *in, size_t n)
{
  size_t i;

  for (i = 0; i < n; i += 2) {
    out[i]   = in[i+1];
    out[i+1] = in[i];
  }
}

// Copies in to out, but removes leading and trailing whitespace.
void trim(char *out, const char *in)
{
  int i, first, last;
  
  // Find the first non-space character (maybe none).
  first = -1;
  for (i = 0; in[i]; i++)
    if (!isspace(in[i])) {
      first = i;
      break;
    }
    
  if (first == -1) {
    // There are no non-space characters.
    out[0] = '\0';
    return;
  }

  // Find the last non-space character.
  for (i = strlen(in)-1; i >= first && isspace(in[i]); i--)
    ;
  last = i;

  strncpy(out, in+first, last-first+1);
  out[last-first+1] = '\0';
}

void formatdriveidstring(char *out, const char *in, int n)
{
  char tmp[65];

  n = n > 64 ? 64 : n;
  swapbytes(tmp, in, n);
  tmp[n] = '\0';
  trim(out, tmp);
}

static unsigned char buff[2048];

int main(int argc, char* argv[]) {
  int fd;
  char hdev[16];
  char mod[64];
  unsigned char cmd;
  struct ata_smart_errorlog *data;
  struct hd_driveid *drive;
  data = (struct ata_smart_errorlog *)&buff;
  drive = (struct hd_driveid *)(&buff);
  cmd = 1;

  if (argc > 1 && access(argv[1], R_OK) != -1) {
    strncpy(hdev, argv[1], 15);
    hdev[15] = 0;
  } else if (access("/dev/hda", R_OK) != -1) {
    strcpy(hdev, "/dev/hda");
  } else if (access("/dev/sda", R_OK) != -1) {
    strcpy(hdev, "/dev/sda");
  } else {
    printf("no valid hd dev provided or found!");
    exit(1);
  }
  if (argc > 2 && strncmp(argv[2], "el", 2) == 0) {
    cmd = 0;
  }

  memset(buff, 0, 2048);
  //unsigned const char normal_lo=0x4f, normal_hi=0xc2;
  //unsigned const char failed_lo=0xf4, failed_hi=0x2c;
  if (cmd == 0) {
    buff[0]=WIN_SMART;
    buff[1]=1;
    buff[2]=0xd5; // read error log
  } else {
    buff[0]=WIN_IDENTIFY;
  }
  buff[3]=1;
  //buff[4]=normal_lo;
  //buff[5]=normal_hi;
  fd = open(hdev, open_flags);
  ioctl(fd, HDIO_DRIVE_CMD, buff);
  if (cmd == 1) {
    formatdriveidstring(mod, (char *)drive->model+4, 40);
    printf("dev mod: %s\n", mod);
    close(fd);
    return 0;
  }
  printf("sizeof(ata_smart_errorlog) = %u\n", sizeof(ata_smart_errorlog)); 
  if (isbigendian()){
    int i,j;

    printf("isbigendian! swapping data\n "); 
    // Device error count in bytes 452-3
    swap2((char *)&(data->ata_error_count));

    // step through 5 error log data structures
    for (i=0; i<5; i++){
      // step through 5 command data structures
      for (j=0; j<5; j++)
    // Command data structure 4-byte millisec timestamp
    swap4((char *)&(data->errorlog_struct[i].commands[j].timestamp));
      // Error data structure life timestamp
      swap2((char *)&(data->errorlog_struct[i].error_struct.timestamp));
    }
  }
  while (true) {
    for (int i=0;i<512;i++) {
      if (i%16 == 0) {
        printf("\n");
      }
      printf("%02x ", buff[i+4]); 
    }
    printf("buff addr 0x%x\n", &buff);
    sleep(1);
    printf("head: %02x %02x %02x %02x \n", buff[0], buff[1],  buff[2],  buff[3]);
    memset(buff, 0, 512);
    buff[0]=WIN_SMART;
    buff[1]=1;
    buff[2]=0xd5; // read error log
    buff[3]=1;
    ioctl(fd, HDIO_DRIVE_CMD, buff);
  }
  close(fd);
  return 0;
}
