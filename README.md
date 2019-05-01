# php-parsers
**atmolog.php** (and accompanying **atmolog_cfg.php**) replaces **acpwu.php** as main data parser and DB storage script. New users should * ***not*** * use acpwu.php. For existing installations, **atmolog.php** is drop-in replacable after modifying **atmolog_cfg.php** passkey variable.

## Installation
### 1. Changing passkey
Edit **atmolog_cfg.php** in a text or code editor. The first line of code is the passkey definition variable that you should change to something unique. This will secure your PHP script from unauthorized access. As an example we will set the passkey to * *passkey1* * but you should define your own unique passkey using up to 8 characters:

``$my_passkey = "passkey1";``

Save the changes and close the editor.

### 2. Installing the script
Using an FTP client or website online tools, create a dedicated directory on your web space for ATMOCOM PHP scripts and data logging. Upload both **atmolog.php** and **atmolog_cfg.php** which you modified in step 1 to the newly created directory.

The script can now be invoked by entering the full URL address in your browser's address bar. For example, let us assume that a website on URL http://www.mywebsite.com has the script placed in a dedicated directory named 'atmocom'. In this example the script would be invoked using the following URL: http://www.mywebsite.com/atmocom/atmolog.php

Accessing the script without any parameters will result in a blank page in your web browser. Also note that HTTPS protocol is currently not supported by ATMOCOM Internet functions, therefore the URL must be accessible over regular HTTP.

### 3. Testing the script
The ATMOCOM parser script includes simple test functionality to verify that file permissions are correctly set and that the SQLite database engine works correctly. To run the test you can invoke the script in any web browser by typing the appropriate URL and parameters in the browser address bar. Following the examples above the script test function would be invoked as follows: 

http://www.mywebsite.com/atmocom/atmolog.php?passkey=passkey1&test=y

The URL parameter passkey must match the PHP variable $my_passkey defined in step 1.

If the test completes successfully you will see this output in your browser window:

```
ATMOCOM parser function test -- Wed, 01 May 2019 12:32:43 +0200

0. Passkey match, proceeding with tests
1. Creating TEMP SQLite database wxdb/temp.db...OK
2. Inserting data into TEMP database...OK
3. Fetching inserted data from TEMP database: Array ( [0] => Array 
( [ID] => 1 [DATA1] => 2019-05-01 [DATA2] => 12:32:43 ) ) ...OK
4. Deleting TEMP database file......OK

SUCCESS! All tests passed, you are good to go!
```

In case any of the steps fail please review your web space file and directory permissions and that SQLite functions are in fact enabled. If needed contact your web hosting provider technical support who should be able to help you sort out any issues.

With the script installation completed you can now configure ATMOCOM to replicate weather station readings to the atmolog.php script. For ATMOCOM configuration specifics please refer to the user manual. Once the entire setup is configured your weather data will be saved in SQLite archives on your website. Additionally a data file is generated which contains current and cumulative data that various third party applications such as weather templates can use for their display.
