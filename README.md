ci-email-validator
==================

PHP email validator class that currently supports:

- RCF email validation 
- Spell checker eg: yahoo.c.uk instead of yahoo.co.uk. yahoo.cpm, hoitmail.co.uk etc.
- MX Record validation
- Disposable domain check
- Connect to the mail server and test if account exists
  Problems:
   - It's common for large ISPs to block outbound connections on port 25. Try running: 
     telnet gmail-smtp-in.l.google.com 25 to test your connection.
   - If you are using firewall check if apache user ("www-data") can access port 25.
