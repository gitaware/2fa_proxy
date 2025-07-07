# 2FA Proxy
2FA Proxy is a 2FA proxy portal for non SAML/OpenID/2FA enabled web applications running under Apache.  
It is probably easy to use this application also under NGINX, but this has not been tested.  

## Admin Interface
The admin interface is build with modern interfaces in mind. It relies on datatables, bootstrap and ajax to view users. Clicking on the 
`admin` or `active` columns will toggle these columns for the user in that row. 
![Admin Interface](img/admin_list.png)

## 2FA Providers
2FA Proxy currently supports two 2FA providers:
- TOTP (Google Authenticator)
- Email One-Time-Code

You can find these providers in `2fa/src/Providers`. Feel free to add your own here and if you feel it is relevant for the 
community, feel free to create a PR.
![TOTP 2FA](img/totp_2fa.png)  
![Email 2FA](img/email_2fa.png)

## Installation
- Copy `2fa` and `application` folders to a web folder. Application is an "example" application. This will be your own application eventually.
- in /2fa run `composer update` to install all dependancies
- the .htaccess file in /application manages redirecting to the 2FA portal if not already authenticated
- /2fa/admin/install runs a check whether all files have the correct owners

## Trouble shooting
### I get an error `file not found`
Please check if `composer update` installed both the `vendor` and `public` folders

### When i go to application i see no 2FA prompt
Likely .htaccess is not copied to the `application` folders. Another reason can be that your `AllowOverride` are too strict.
If you are not using Apache but NGINX, you can probably also make this work, but then you need to write your own redirect based 
on the `.htaccess` file in the `application` folder.
