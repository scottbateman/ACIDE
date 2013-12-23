# ACIDE INSTALLATION
----------------------------------------------------------------------

To install simply place the contents of the system in a web accessible folder.


## Write capabilities

Ensure that the following directories/files have write capabilities:

    /config.php
    /data (including subfolders)
    /workspace
    /javaws_workspace

## Java WS Configuration

For the 'java' command work properly in the terminal and the generated `.class` file run by the browser automatically, you have to configure the system so it can generate and sign jar files, which contains the class files created by a user. There are two ways to do this: 1) get a certificate from a signing authority; 2) create your own certificate.

The advantage of having a certificate from an authority is that it allows Java to run the jar files through Java WebStart without gettig permission from the user. If you create your own certificate, the user will have to click on a confirmation box before being able to run their class files. Certificate services can be expensive (e.g., $300-$900/year); however, one service offers free certificates for open-source developpers (http://www.certum.eu/).


#### Getting your own certificate
Copy your certificate file that you acquired from the signing authority to the "javawx_workspace" directory.

Directions are here (these work with http://www.certum.eu/): 
 - http://stackoverflow.com/questions/19458676/signing-a-jar-file-with-trusted-certificate-for-jws-deployment
 - http://stackoverflow.com/questions/4210254/how-to-sign-a-jar-file-using-a-pfx-file

#### Creating the keystore file
Change to the "javawx_workspace" directory.

To sign a jar, we must first have a keystore (a private key) to do the signing. 

To generate a keystore we use the keytool that is part of the Java SDK.

    keytool -genkey -keystore keystore_file.keys -alias http://your_website.ca/ -validity 365

You will have to provide your information and a password.

This will create a new keystore in the file keystore_file.keys. 

The alias is typically the domain of the site you want to sign for, and the validity is the number of days until the keys will expire. 

In this case we would have to generate a new key in a year.

#### Compile your own Console.jar
    
The console is used to run all java files created in ACIDE on the user's local computer. The console is itself downloaded as a jar file when the user executes a command using 'java classname'. The source code is included, so that the Console can be customized. The provided Console.jar file contains a precompiled and packaged Console.
    
    `javac Console.java`
    `jar cf Console.jar *.class`
    
    Add permission attributes to the MANIFEST file inside this jar
    `jar ufm Console.jar BASE-MANIFEST.MF`
    
    
#### Sign the Console.jar file

To sign the Console.jar file using the keystore you just created, use the following command (Make sure you: overwrite 'password' with the password you just used to create the keystore and that you are in the website root's directory):

    jarsigner -keystore ./javaws_workspace/keystore_file.keys -storepass 'password' \
    ./javaws_workspace/jnlp_xml/Console.jar ALIAS
    
if you are using a pfx file then you will need to specify the storetype
    
    -storepass pkcs12


#### Updating the file `term.php` to point to the `.keys` file path

Open the file `components/terminal/emulator/term.php`.

Go to the line that looks like this:
    `system("jarsigner -keystore /var/codiad_files/jaxb.keys -storepass 'keystore password' " . $jar_path_and_name . " alias");`

Edit the following:
  - Overwrite `/var/codiad_files/jaxb.keys` to point to the `.keys` file you just created.
  - Overwrite `keystore password` with the password you used to generate the keystore.
  - Overwrite `alias` with your own website address or the alias you have for your key.
  
## Update config.php
In the base directory update the `config.php` file to contain the correct BASE_PATH, WEB_BASE_PATH, TIMEZONE, etc.


## MongoDB Installtion

ACIDE uses the database system MongoDB and the MongoDB driver for PHP. 

Both have to be installed in your server.

#### Installing MongoDB

Follow the tutorial in the **[MongoDB website](http://docs.mongodb.org/manual/tutorial/install-mongodb-on-ubuntu/)** .

#### Installing the MongoDB PHP driver.

Follow the tutorial in the **[PHP website](http://php.net/manual/en/mongo.installation.php)**.

## System Installation
    
Open the URL corresponding to where the system is placed and the
installer screen will appear. If any dependencies have not been met the
system will alert you.

Enter the requested information to create an administrator account:

    - Username (whithout spaces).
    - Password
    - E-mail
    - Database Name (without spaces)
    - Timezone
    
and submit the form using the 'Submit' button in the bottom.
    
If everything goes as planned 
you will be greeted with a login screen.

Log in using the administrator account you just created.

After logging in as the admin:

 - Create a new course.
 - Create a user in the professor role and add them to the course.
 - Logged in as the professor add students to the course.
 
#### DO NOT use the admin account to manage courses, it has not been tested. Courses should be adminstered by accounts in the professor role.
