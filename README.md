# PHP Airtable Wrapper
PHP Airtable Wrapper for the Airtable API

## Getting Started

Follow these steps to successfully implement this PHP Airtable Wrapper.

*NOTE: The Airtable API allows one to implement basic CRUD functions on records, but does not allow you to make changes to the schema of the tables. Use their interface to make changes to the schema*

### Getting your API Key and Base ID

Two tokens are required to use this wrapper: an api token, and a base id. Follow these steps to get the token:

><kbd><img src="documentation/Image1.jpg" height="200px"></img></kbd>
>
>1. Login to Airtable and click your profile picture.
>2. Click on "Accounts".
>
><kbd><img src="documentation/Image2.jpg" height="100px"></img></kbd>
>
>3. Scroll down to "<> API".
>4. Copy the personal API Key.
>
><kbd><img src="documentation/Image3.jpg" height="200px"></img></kbd>
>
>5. Navigate to the base you want to access with this wrapper.
>6. Click on the Help icon.
>7. Click on "<> API Documentation".
>
><kbd><img src="documentation/Image4.jpg" height="100px"></img></kbd>
>
>8. Look at the URL and find the portion of the url that begins with 'app'.
>9. Copy your base ID.

### Class Installation

You can either use composer or download the classes directly. To download using composer, run the following command:

```
composer require pcbowers/php-airtable
```

If you are using composer, run the autoloader to include the various classes in your project:

```php
require 'vendor/autoload.php';
```

If you downloaded the classes directly, include the airtable.php file:

```php
include('../src/airtable.php');
```

### Initialize the Class

Use the following code snippet with your API Key and Base ID to begin using the wrapper:

```php
use \pcbowers\Airtable\airtable;
$airtable = new Airtable(array(
    'api_key' => 'api_key',
    'base_id' => 'base_id'
));
```

## Examples

A number of examples are placed below to help use this wrapper. The examples assume that the class has already been initiated.

### getApiKey & getBaseId

Returns the API Key and Base ID respectively for a specific instance.

##### Code Example:
```php
echo $airtable->getApiKey() . "<br />";
echo $airtable->getBaseId();
```

##### Result:
```
your_api_key
your_base_id
```

### listRecords

Returns a list of records from a given table.

```
$table_name: String. Required.
$params: Array. All parameters are optional.

For more details on accepted parameters, see the airtable documentation.
```

A special parameter not included on the Airtable API has been added called checkOffset. This paramater defaults to true but can be set to false. It allows you to return just 1 page of results instead of looping through all of them. (Depending on your rate limits, any more than 500 records in 1 search may return false if those rate limits are reached).

##### Code Template:
```php
$airtable->listRecords($table_name, array(
    "fields" => array(strings),
    "filterByFormula" => string,
    "maxRecords" => number,
    "pageSize" => number,
    "sort" => array(objects),
    "view" => string,
    "cellFormat" => string,
    "timeZone" => string,
    "userLocale" => string,
    "checkOffset" => true
));
```


##### Code Example:
```php
print_r($airtable->listRecords("Users", array(
    "fields" => array("First Name", "Last Name"),
    "sort" => array(array("field" => "First Name", "direction" => "asc")),
    "maxRecords" => 100
)));
```

##### Result:
```
Array of records. Contains:
    - up to 100 records
    - 'First Name' and 'Last Name' field
    - id of each record along with the created time
    - sorted by 'First Name ascending'

Because checkOffset was not set to false, all pages possible were returned.
```

### retrieveRecord

Retrieves a specific record from a given table. Empty fields from record are not returned.

```
$table_name: String. Required.
$record_id: String. Required.
```

##### Code Template:
```php
$airtable->retrieveRecord($table_name, $record_id);
```

##### Code Example:
```php
print_r($airtable->retrieveRecord("Users", "recfauP0XQTTgXMQK"));
```

##### Result:
```
Array with all the information from one record that is not empty.
```

### createRecord

Create a record in a given table.

```
$table_name: String. Required.
$data: Array. Optional (though recommended).
```

##### Code Template:
```php
$airtable->createRecord($table_name, array(
    "field_name1" => "field_value1",
    "field_name2" => "field_value2"...
));
```

##### Code Example:
```php
print_r($airtable->createRecord("Users", array(
    "First Name" => "Joe",
    "Last Name" => "Smith"
)));
```

##### Result:
```
On success, the created record is returned with the field information and id of the record.
```

### updateRecord

Update a specific record from a given table.

```
$table_name: String. Required.
$record_id: String. Required.
$data: Array. Optional (though recommended).
$destructive: Boolean. Optional. Default false.
A value of false runs a PATCH update leaving data untouched if not edited.
A value of true runs a PUT update which destroys the instance and updates it with only the new data added.
```

##### Code Template:
```php
$airtable->updateRecord($table_name, $record_id, array(
    "field_name1" => "field_value1",
    "field_name2" => "field_value2"...
), $destructive);
```

##### Code Example:
```php
print_r($airtable->updateRecord("Users", "recAkSf8l5IpITPV8", array(
    "First Name" => "Joe1",
    "Last Name" => "Smith1"
)));
```

##### Result:
```
On success, updated record returned with the new information.
$destructive was not set to true, so the rest of the record's information remained intact.
```

### deleteRecord

Deletes a specific record from a given table.

```
$table_name: String. Required.
$record_id: String. Required.
```

##### Code Template:
```php
$airtable->deleteRecord($table_name, $record_id);
```

##### Code Example:
```php
print_r($airtable->deleteRecord("Users", "recU84ywe5m1md4wP"));
```

##### Result:
```
On success, returns the fact that it was deleted along with the id of the deleted record.
```

### getLastLog & getLog

Error logging is supported so that your program does not crash on request errors. Any of the functions that require Curl Requests will return false on failure or the record(s) at hand on success. On top of that, each failure or success is logged within the wrapper instance. The following code shows how to access the log:

##### Code:
```php
print_r($airtable->getLog());
echo "<br />";
print_r($airtable->getLastLog());
```

##### Result:
```
your_entire_log
last_entry_of_log
```
