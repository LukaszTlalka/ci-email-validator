ci-email-validator
==================

Just another PHP email validation class. Current support:

- RCF email validation.
- Spell checker eg: yahoo.c.uk instead of yahoo.co.uk. yahoo.cpm, hoitmail.co.uk etc.
- MX Record validation.
- Disposable domain check.
- Check if account exists on the mail server. 


Problems with email account validation on the Mail Server:
   - It's common for large ISPs to block outbound connections on port 25. Try running:
     telnet gmail-smtp-in.l.google.com 25 to test your connection.
   - If you are using firewall check if apache user ("www-data") can access port 25.
   - If you are running PHP version less than 5.3.11 validateAccountOnMailServer method will return false due to the PHP bug.
