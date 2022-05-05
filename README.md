# Biller WooCommerce plugin

### Working with translations
- Step 1: To create or update pot file run following command:
```
./biller i18n:make:pot
```
- Step 2: Notify Biller team about newly created/updated translations.
- Step 3: If translations are provided inside .po files, add these files 
and run following command in order to create mo files
```
./biller i18n:make:mo
```
- If translations are provided in CSV or as plain text use Poedit 
visual translation editor tool (https://poedit.net) to create po and mo files.