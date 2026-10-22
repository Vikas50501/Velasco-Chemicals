# SMTP setup for the enquiry form

Configure these environment variables for the PHP site in Hostinger. The form endpoint reads them directly; it does not load a `.env` file in production.

```text
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_ENCRYPTION=tls
MAIL_SMTP_USERNAME=yavish50501@gmail.com
MAIL_SMTP_PASSWORD=<new Google App Password>
MAIL_FROM_ADDRESS=yavish50501@gmail.com
MAIL_FROM_NAME=Velasco Chemicals
MAIL_TO_ADDRESS=velascochemicals@gmail.com,yavish50501@gmail.com
```

Use a newly generated Google App Password. Do not use the previous one, and do not place it in the repository or site files.

After saving the variables, submit a real enquiry through the published website and confirm that it arrives at each address in `MAIL_TO_ADDRESS`.
