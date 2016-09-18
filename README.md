# PHP Salesforce Web-To-Lead Form Parser

Composer Package of Salesforce Web-To-Lead Form Parser

To use call `parse` function with file path. It will return an array
with all the fields in the Web-To-Lead form at that path.

Web-To-Lead file can be generated in salesforce.com account. The best
way to go is to include all available fields. That will give more 
flexibility for future development. Salesforce.com does not have 
required fields in web-to-lead forms so it will accept even if you send 
partial data.

To send data you will need `sf-web2lead-submitter` package which is 
based on this parser.