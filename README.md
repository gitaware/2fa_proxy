# 2fa_proxy
2FA proxy is a 2FA proxy portal for non SAML/OpenID/2FA enabled web applications running under Apache.  
it is probably easy to use this application also under NGINX, but this has not been tested.

## Installation
- Copy all files to a web folder
- the .htaccess file in /application manages redirecting to the 2FA portal if not already authenticated
- /2fa/admin/install runs a check whether all files have the correct owners
