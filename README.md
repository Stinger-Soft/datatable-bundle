# Data Table Bundle
The Data Table Bundle allows to create tables based on Doctrines Query Builders and Queries in a style similar to Symfony Forms.

## Installation
### Add the bundle to your composer.json
Via Composer directly

```bash
php composer.phar require pec-platform/datatable-bundle dev-develop
```

or add the following to your composer.json

```json
{
    "require": {
        "pec-platform/datatable-bundle": "dev-develop"
    }
}
```

### Download the newly added bundle
```bash
php composer.phar update pec-platform/datatable-bundle
```

### Register the bundle in your AppKernel.php
``` php
public function registerBundles()
{
    $bundles = array(
        // ...
        new StingerSoft\DatatableBundle\StingerSoftDatatableBundle(),
    );
}
```


## Usage
Similar to the Symfony Form bundle and components, one can use the DataTable Bundle to
render tables with columns and filters etc. using jQuery DataTable.

### Creating a table

In order to use and render a table, you first have to define or create the corresponding table type suitable for your needs (see the [Configuration](#configuration) and especially the [Table Type](#table-type) sections).

Generally, following the [Symfony Form / Type pattern](http://symfony.com/doc/3.2/form/create_custom_field_type.html), you start by defining your own table class type, let it extend the `StingerSoft\DatatableBundle\Table\AbstractTableType`, containing the basic information needed and define the tables' parent type (default) `StingerSoft\DatatableBundle\Table\Table`:

```php
// src/AppBundle/Table/Type/MyTableType.php
namespace AppBundle\Table\Type;

use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Table\AbstractTableType;
use StingerSoft\DatatableBundle\Table\Table;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MyTableType extends AbstractTableType {

    public function buildTable(TableBuilderInterface $builder, array $options) {
        // ...
    }
    
    public function configureOptions(OptionsResolver $resolver) {
        // ...
    }
    
    public function getParent() {
        return Table::class;
    }
    
    public function getId() {
        return 'my-table-type';
    }
}
```

Within the `configureOptions` method you may override default options, add new options etc. Please refer to the list of [default table options](#table-type-options) for more information.

The `getParent` method must return the parent type of the table. When configuring a table type, all the options and columns and view specific variables of all parents and grand-parents (walking down the hierarchy) are configured and built beforehand.

The `getId` method should return a unique, but not random identifier for the table, as the default behaviour is to randomly generate an Id on every call.

Finally, the `buildTable` method is used for actually adding or removing columns to the table type:

```php
// ...
use \StingerSoft\DatatableBundle\Column\StringColumnType;

class MyTableType extends AbstractTableType {

    public function buildTable(TableBuilderInterface $builder, array $options) {
        $builder
            ->add('firstName', StringColumnType::class, array(
            	// column options go here
            ))
            ->add('lastName', StringColumnType::class, array(
            	// column options go here
            ));
    }
    // ...
}
```

The key under which a column is added is used as a property accessor or selector of the tables underlying query builder.

In the above example for instance the table may contain a list of users and the first column of the table will contain a string representation of the property or field `firstName` of every user to be displayed in the table etc. whereas the second column will display the `lastName` of the users.

The key for the column to be added to the table builder may also use the dot notation of the property accessor to access joined properties or fields, such as the city of a users address:

```php
// ...
use \StingerSoft\DatatableBundle\Column\StringColumnType;

class MyTableType extends AbstractTableType {

    public function buildTable(TableBuilderInterface $builder, array $options) {
        $builder
            ->add('address.city', StringColumnType::class, array(
            	// column options go here
            ));
    }
    // ...
}
```

**Please note that in this case the `address` entity must be joined already in the underlying query!**

For a complete list of column types and their options refer to the [Column Types](#column-types)

### Using and rendering a table

When you have the correct table type at hand, the `stinger_soft_datatable.datatable_service` service can be used to generate a table for a specific query builder:

#### Using a table

```php
public function indexAction(\Symfony\Component\HttpFoundation\Request $request) {
    // first we obtain the service needed for generating tables
    /** @var \StingerSoft\DatatableBundle\Service\DatatableService $service */
    $service = $this->container->get('stinger_soft_datatable.datatable_service');

    // now we get the querybuilder allowing us to get data for the table
    /** @var \Doctrine\ORM\QueryBuilder $qb */
    $qb = $this->getQueryBuilder();

    // finally we create the table, specifying the desired type, query builder and any additional options 
    /** @var \StingerSoft\DatatableBundle\Table\Table $table */
    $table = $service->createTable(MyTableType::class, $qb, array(
        // provide custom options for the table here
    ));
    // ...
}
```

#### Providing a table view and rendering the table

##### Controller specific code
In order to display the generated table, you need to create a view for it, which can be forwarded to your templating engine (i.e. twig) like this:
```php
public function indexAction(\Symfony\Component\HttpFoundation\Request $request) {
	// get service, get query builder, ...
	
    // we create the table, specifying the desired type, query builder and any additional options 
    /** @var \StingerSoft\DatatableBundle\Table\Table $table */
    $table = $service->createTable(MyTableType::class, $qb, array(
        // provide custom options for the table here
    ));

    // and create a view for it, passing it to the template to be rendered
    return $this->render('StingerSoftDatatableBundle:Demo:index.html.twig', array(
        'table' => $table->createView() 
    ));
}
```

##### Twig specific code

###### Including the assets
First you need to make sure that all the required (S)CSS and Javascript files required for the jQuery Datatable are imported in your template:
```twig
<html>
    <head>
        {# this will include all the css/scss assets required for table styling #}
        {% include 'StingerSoftDatatableBundle::styles.html.twig' %}
        
        {# this will include all the required javascript assets #}
        {% include 'StingerSoftDatatableBundle::scripts.html.twig' %}
    </head>
</html>
```

###### Displaying the table
When it comes to actually displaying the table view and initializing the jQuery data tables object on it, you can simply include a pre-configured template:
```twig
<body>
    <div id="my-table-container">
        {# here we include our table #}
        {{ datatable_table_render(table) }}
    </div>
</body>

```

### Handling requests for server side tables

When dealing with server side handling of tables, your controller needs to act on the table in case filtering, searching, sorting or similar, such as pagination, is applied.

As the request containing new ordering, filtering, searching, paging options etc. should only update the data of the table but now the view itself, you must tell the table to handle the request and then return the new / updated data correspondingly:

```php
public function indexAction(\Symfony\Component\HttpFoundation\Request $request) {
	// get service, get query builder, ...
	
    // again we create the table, specifying the desired type, query builder and any additional options 
    /** @var \StingerSoft\DatatableBundle\Table\Table $table */
    $table = $service->createTable(MyTableType::class, $qb, array(
        // provide custom options for the table here
    ));
    
    // the request for updating the table is always an ajax request
    if($request->isXmlHttpRequest()) {
        // here we updat the table
        $table->handleRequest($request);
        
        // now we return the json response with the updated data
        return $table->createJsonDataResponse();
    } else {
    // else we create a view for the table, passing it to the template to be rendered
        return $this->render('StingerSoftDatatableBundle:Demo:index.html.twig', array(
            'table' => $table->createView() 
        ));
    }
}
```

## Configuration

### Table Type

#### Table Type Options

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#serverside"><code>serverSide</code></a></li>
                <li><a href="#processing"><code>processing</code></a></li>
                <li><a href="#paging"><code>paging</code></a></li>
                <li><a href="#scroller"><code>scroller</code></a></li>
                <li><a href="#ajax-url"><code>ajax_url</code></a></li>
                <li><a href="#ajax-method"><code>ajax_method</code></a></li>
                <li><a href="#deferrender"><code>deferRender</code></a></li>
                <li><a href="#stavesave"><code>staveSave</code></a></li>
                <li><a href="#stateduration"><code>stateDuration</code></a></li>
                <li><a href="#dom"><code>dom</code></a></li>
                <li><a href="#lengthmenu"><code>lengthMenu</code></a></li>
                <li><a href="#pagelength"><code>pageLength</code></a></li>
                <li><a href="#pagingtype"><code>pagingType</code></a></li>
                <li><a href="#scrollcollapse"><code>scrollCollapse</code></a></li>
                <li><a href="#scrollx"><code>scrollX</code></a></li>
                <li><a href="#scrolly"><code>scrollY</code></a></li>
                <li><a href="#order"><code>order</code></a></li>
                <li><a href="#rowid"><code>rowId</code></a></li>
                <li><a href="#rowclass"><code>rowClass</code></a></li>
                <li><a href="#rowdata"><code>rowData</code></a></li>
                <li><a href="#rowattr"><code>rowAttr</code></a></li>
                <li><a href="#translation-domain-for-table"><code>translation_domain</code></a></li>
                <li><a href="#attr-for-table"><code>attr</code></a></li>
                <li><a href="#classes"><code>classes</code></a></li>
                <li><a href="#filter-external"><code>filter_external</code></a></li>
                <li><a href="#sort-on-header-label"><code>sort_on_header_label</code></a></li>
                <li><a href="#column-groups"><code>column_groups</code></a></li>
                <li><a href="#search-enabled"><code>search_enabled</code></a></li>
                <li><a href="#search-placeholder"><code>search_placeholder</code></a></li>
                <li><a href="#reload-enabled"><code>reload_enabled</code></a></li>
                <li><a href="#reload-tooltip"><code>reload_tooltip</code></a></li>
                <li><a href="#clear-enabled"><code>clear_enabled</code></a></li>
                <li><a href="#clear-tooltip"><code>clear_tooltip</code></a></li>
                <li><a href="#state-save-key"><code>state_save_key</code></a></li>
                <li><a href="#order-state-save-key"><code>order_state_save_key</code></a></li>
                <li><a href="#filter-state-save-key"><code>filter_state_save_key</code></a></li>
                <li><a href="#search-state-save-key"><code>search_state_save_key</code></a></li>
                <li><a href="#visibility-state-save-key"><code>visibility_state_save_key</code></a></li>
                <li><a href="#page-length-state-save-key"><code>page_length_state_save_key</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Parent type </td>
        <td>none</td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Table\TableType</code></td>
    </tr>
  </tbody>
</table>

---

##### serverSide
**type:** `boolean` **default** `true`

Toggles whether or not sorting, filtering, searching etc. is to be handled on the server or the client side.

In case `true` is given, a value for the [`ajax_url`](#ajax-url) option must be provided in order to set the data source
used for filtering, sorting and searching.

**Related**: _jQuery Datatable Option_ [`serverSide`](https://datatables.net/reference/option/serverSide)

---

##### processing
**type:** `boolean` **default** `true`

Enable or disable the display of a 'processing' indicator when the table is being processed (e.g. a sort). This is particularly useful for tables with large amounts of data where it can take a noticeable amount of time to sort the entries.

**Related**: _jQuery Datatable Option_ [`processing`](https://datatables.net/reference/option/processing)

---

##### paging
**type:** `boolean` **default** `true`

Enables the splitting of rows over several pages, which is an efficient method of showing a large number of records in a small space.

The number of elements per page can be defined via the [`lengthMenu`](#lengthmenu) and [`pageLength`](#pagelength) options, whereas the kind of pagination to be used can be defined by the [`pagingType`](#pagingtype) option.

*Please note*: when setting `paging` to false, the [`scroller`](#scroller) plugin (i.e. infinite scroll) cannot be used.

**Related**: _jQuery Datatable Option_ [`paging`](https://datatables.net/reference/option/paging)

---

##### scroller
**type:** `boolean` **default** `true`

Toggles the infinite scroll ability of the table.
When `true` is given, items will be added / replaced only as needed when the user is scrolling the table body and **no pagination** will work, but the option [`paging`](#paging) must be set to `true` (it is by default).

**Related**: _jQuery Datatable Option_ [`scroller`](https://datatables.net/reference/option/scroller)

---

##### ajax_url
**type:** `string` **default** `null`
The URL to be used for retrieving data for the table. This URL will also be requested in case a new ordering is applied, a search is executed, data is paginated etc.

**Related**: _jQuery Datatable Option_ [`ajax`](https://datatables.net/reference/option/ajax)

---

##### ajax_method
**type:** `string` **default** `POST`

The method to be used when performing ajax requests for getting data.

Allowed values are:

- `POST`
- `GET`

**Related**: _jQuery Datatable Option_ [`ajax`](https://datatables.net/reference/option/ajax)

---

##### deferRender
**type:** `boolean` **default** `true`

Allows to define whether rendering of table cells will be deferred until the initialization of tables is done and the number of entries for the table are determined.

As an example to help illustrate this, if you load a data set with 10,000 rows, but a paging display length of only 10 records, rather than create all 10,000 rows, when deferred rendering is enabled, DataTables will create only 10.

**Related**: _jQuery Datatable Option_ [`deferRender`](https://datatables.net/reference/option/deferRender)

---

##### staveSave
**type:** `boolean` **default** `true`

Allows to define whether the currently applied settings for the table, such as sorting, column visibility, searching, filtering, page-length etc. are to persisted or not.

Persisting in this case means, that the settings will be stored on the client side and not on the server side. The storage target (local-storage or session-storage) can be defined by using the [`stateDuration`](#stateduration) option.
Persisted settings will be restored automatically when a table is initialized and as such, a previously applied search, filtering, sorting etc. will be restored.
  You can define which setting will be persisted more or less individually by the following options:

<table class="table table-hover table-striped">
    <thead>
        <tr>
            <th>Option</th>
            <th>Influence</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><a href="#state-save-key">state_save_key</a></td>
            <td>The key under which general table settings shall be persisted</td>
        </tr>
        <tr>
            <td><a href="#stateduration">stateDuration</a></td>
            <td>The number of seconds to store the information, also determining whether to use <code>local-storage</code> or <code>session-storage</code></td>
        </tr>
        <tr>
            <td><a href="#search-state-save-key">search_state_save_key</a></td>
            <td>The key under which the tables global search shall be persisted</td>
        </tr>
        <tr>
            <td><a href="#filter-state-save-key">filter_state_save_key</a></td>
            <td>The key under which the tables column specific filter values shall be persisted</td>
         </tr>
         <tr>
           <td><a href="#visibility-state-save-key">visibility_state_save_key</a></td>
            <td>The key under which the tables column specific visibility shall be persisted</td>
         </tr>
         <tr>
           <td><a href="#page-length-state-save-key">page_length_state_save_key</a></td>
            <td>The key under which the tables page length (i.e. number of rows to be displayed when paginating) shall be persisted</td>
        </tr>
        <tr>
            <td><a href="#order-state-save-key">order_state_save_key</a></td>
            <td>The key under which the tables sorting/ordering of columns shall be persisted</td>
        </tr>
   </tbody>
</table>

**Related**: _jQuery Datatable Option_ [`staveSave`](https://datatables.net/reference/option/staveSave)

---

##### stateDuration
**type:** `integer` **default** `0`

Determines how many seconds the table settings are persisted on the client side as well as which storage method to be used.

Value treatment:

| Value           | Consequences                                                                                                                          |
|:----------------|:--------------------------------------------------------------------------------------------------------------------------------------|
| `-1`            | Uses `session-storage` for persisting, meaning that the settings will be lost when the browser window containing the table is closed. |
| `0`             | Uses `local-storage` for persisting and the table settings will never be lost only when they are deleted manually                     |
| any value `> 0` | Uses `local-storage` for persisting and the table settings will be kept for the given amount of time in seconds                       |

**Related**: _jQuery Datatable Option_ [`stateDuration`](https://datatables.net/reference/option/stateDuration)

---

##### dom
**type:** `string` or `null` **default** `<'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>`

Determines how the table is placed in the DOM within a potential container, allowing to specify classes, places where to put pagination etc.

_Please note_: by default, the additional controls such as search and page length are **not** part of the `dom` property as these are rendered individually via twig when using the default twig files (`StingerSoftDatatableBundle:Table:table.html.twig`). Additional Javascript listeners are implemented as part of the PEC specific extension of the default jQuery Datatable which ensure correct behaviour, such as selecting a page from the page length menu triggers a reload etc. So in case you change the `dom` property, make sure to have a look at the corresponding default twig template.

**Related**: _jQuery Datatable Option_ [`dom`](https://datatables.net/reference/option/dom)

---

##### lengthMenu
**type:** `null` or `array` **default** `null`

Determines the entries displayed in the menu that allows setting the number of entries to show per page in a table.

A (default) value of `null` means that no menu is displayed and the table always uses the number of entries specified via the [`pageLength`](#pageLength) option.

Additionally an array containing integers may be provided. A value of `array(10, 25, 50, 100)` would allow the user to either display 10, 25, 50 or 100 items per page.

**Related**: _jQuery Datatable Option_ [`lengthMenu`](https://datatables.net/reference/option/lengthMenu)

---

##### pageLength
**type:** `integer` **default** `10`

Specifies the initial amount of entries to be shown per page. A user may override this by selecting another value from the paging menu, to be specified via the [`lengthMenu`](#lengthmenu) option.

**Related**: _jQuery Datatable Option_ [`pageLength`](https://datatables.net/reference/option/pageLength)

---

##### pagingType
**type:** `string` **default** `simple_numbers`

Determines how the pagination shall be rendered, allowing to show either page numbers, first and/or last buttons etc.

The following values are possible:

<table class="table table-hover table-striped">
    <thead>
        <tr>
            <th>Value</th>
            <th>Outcome</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>numbers</td>
            <td>Page number buttons only</td>
        </tr>
        <tr>
            <td>simple</td>
            <td>'Previous' and 'Next' buttons only</td>
        </tr>
        <tr>
            <td>simple_numbers</td>
            <td>'Previous' and 'Next' buttons, plus page numbers</td>
        </tr>
        <tr>
            <td>full</td>
            <td>'First', 'Previous', 'Next' and 'Last' buttons</td>
        </tr>
        <tr>
            <td>full_numbers</td>
            <td>'First', 'Previous', 'Next' and 'Last' buttons, plus page numbers</td>
        </tr>
        <tr>
            <td>first_last_numbers</td>
            <td>'First' and 'Last' buttons, plus page numbers</td>
        </tr>
   </tbody>
</table>

**Related**: _jQuery Datatable Option_ [`pagingType`](https://datatables.net/reference/option/pagingType)

---

##### scrollCollapse
**type:** `boolean` **default** `false`

Determines whether the table shall be reduced in height when a limited number of rows are shown.

**Related**: _jQuery Datatable Option_ [`scrollCollapse`](https://datatables.net/reference/option/scrollCollapse)

---

##### scrollX
**type:** `boolean` **default** `false`

Specifies whether content of the table will be scrollable horizontally or not.

In case the content of the table does not fit the container, a horizontal scrollbar can be shown if `scrollX` is set to `true`.

**Related**: _jQuery Datatable Option_ [`scrollX`](https://datatables.net/reference/option/scrollX)

---

##### scrollY
**type:** `string` or `null` **default** `null`

Enables or disables vertical scrolling of the tables content in case a certain height is reached.

The value given here can be any CSS unit (such as `pt`, `mm`, `%`, `px` etc.) , or a number (in which case it will be treated as a pixel measurement) and is applied to the table body (i.e. it does not take into account the header or footer height directly).

**Related**: _jQuery Datatable Option_ [`scrollY`](https://datatables.net/reference/option/scrollY)

---

##### order
**type:** `null` or `array` **default** `null`

Defines a default sort order for the table. If `null` (default) is used, the table will not be sorted initially and data is ordered according to the underlying query builder.

In case an ordering shall be applied, a 2-dimensional array must be provided, where each value is an array containing column index and desired sort direction (either `asc` or `desc`), like this:

```php
    array (
 	    array(0, 'asc'), 
 	    array(1, 'desc')
 	);
```

**Related**: _jQuery Datatable Option_ [`order`](https://datatables.net/reference/option/order)

---

##### rowId
**type:** `null`, `string`, `callable` **default** `null`

Defines a property accessor or delegate to be used for determining the HTML `id` attribute of the table row to be inserted for every element in the table.

If a `string` is given, it will be used as a property accessor for retrieval of an appropriate Id value. For instance, if the table contains users and every user has an id property or field, the `rowId` can simply be `user.id` or even `id` (if the root alias for the underlying query builder is `user`). If no such attribute or field can be found, the string is used as is and simply applied as the Id of the table row.

If a `callable` or delegate is given, the delegate must return a string value and will be evaluated with the item to be placed in the table row as its first parameter and the table options as its second parameter:

```php
// ...

/** @var \StingerSoft\DatatableBundle\Table\Table $table */
$table = $service->createTable(MyTableType::class, $qb, array(
    'rowId' => function(MyObject $object, array $options) {
    	if($object->appendPrefix()) {
    	    return $object->getUniqueId(true);
    	} else {
    		return $object->getUniqueId(false);
    	}
    }
));

```

**Related**: _jQuery Datatable Option_ [`DT_RowId`](https://datatables.net/manual/server-side#DataTables_Table_2_wrapper) in the returned data for `serverSide` processing

---

##### rowClass
**type:** `null`, `string`, `callable` **default** `null`

Defines the class(es) to be appended to the list of classes of the table row to be inserted for every element in the table.

If a `string` is given, it will be added to the default class list. In case more than one class shall be added, simply seperate the classes by spaces ("`class1 class2`")

If a `callable` or delegate is given, the delegate must return a string value (seperated by spaces in order to add more than one class) and will be evaluated with the item to be placed in as its first parameter and the table options as its second parameter:

```php
// ...

/** @var \StingerSoft\DatatableBundle\Table\Table $table */
$table = $service->createTable(MyTableType::class, $qb, array(
    'rowClass' => function(MyObject $object, array $options) {
    	if($object->isArchived()) {
    		return 'archived';
    	} else {
    		return 'active';
    	}
    }
));

```

**Related**: _jQuery Datatable Option_ [`DT_RowClass`](https://datatables.net/manual/server-side#DataTables_Table_2_wrapper) in the returned data for `serverSide` processing

---

##### rowData
**type:** `null`, `array`, `callable` **default** `null`

Defines arbitrary data that will appended to table row to be inserted for every element in the table. Data in this case means HTML `data` attributes, which are appended using the [`jQuery.data()`](https://api.jquery.com/data/) API.

If an `array` is given, data will be added accordingly for every `key=value` pair in that particular array.

If a `callable` or delegate is given, the delegate must return `null` or an array value with key value pairs and the delegate will be evaluated with the item to be placed in the table row as its first parameter and the table options as its second parameter:

```php
// ...

/** @var \StingerSoft\DatatableBundle\Table\Table $table */
$table = $service->createTable(MyTableType::class, $qb, array(
	'rowData' => function(MyObject $object, array $options) {
    	if($object->isExtensible()) {
    		// key => value array, resulting in two data attributes
    		return array(
    			'extensible' => true, 
    			'children' => $object->getChildCount()
    		);
    	}
    }
));

```

**Related**: _jQuery Datatable Option_ [`DT_RowData`](https://datatables.net/manual/server-side#DataTables_Table_2_wrapper) in the returned data for `serverSide` processing

---

##### rowAttr
**type:** `null`, `array`, `callable` **default** `null`

Defines arbitrary data that will appended as HTML attributes to table row to be inserted for every element in the table. Every key in the returned array will be used as an additional HTML attribute, which is appended using the [`jQuery.attr()`](https://api.jquery.com/attr/) API.

If an `array` is given, HTML attributes will be added accordingly for every `key=value` pair in that particular array.

If a `callable` or delegate is given, the delegate must return `null` or an array value with key value pairs and the delegate will be evaluated with the item to be placed in the table row as its first parameter and the table options as its second parameter:

```php
// ...

/** @var \StingerSoft\DatatableBundle\Table\Table $table */
$table = $service->createTable(MyTableType::class, $qb, array(
	'rowAttr' => function(MyObject $object, array $options) {
		$attributes = array('title' => $object->getName());
    	if(!$object->hasPicture()) {
    		$attributes['bgcolor'] = '#ffafaf';
    	}
    	return $attributes;
    }
));

```

**Related**: _jQuery Datatable Option_ [`DT_RowAttr`](https://datatables.net/manual/server-side#DataTables_Table_2_wrapper) in the returned data for `serverSide` processing

---

##### translation_domain (for Table)
**type:** `null`, `string`, `boolean` **default** `message`

Defines the translation domain to be used when labels or buttons are to translated.

Setting a value of `false` results in no translation, whereas `true` means to re-use the currently set translation domain.

---

##### attr (for Table)
**type:** `array` **default** `array('class' => 'table table-striped table-hover table-condensed expendable-table')`

Allows to specify HTML attributes for the table. By default only the following CSS classes are set `table table-striped table-hover table-condensed expendable-table`.

_Please note_: The `id` attribute is **NOT to be specified manually**, but by overriding the `getId()` method in the table type. If not overriding the default method, a randomly generated Id will be given to the table upon rendering. As such, for every reload, a new Id will be set for the table

---

##### classes
**type:** `string` or `null` **default** `null`

Allows to add additional CSS classes to be added to the tables default attributes.

In case `null` is given, no additional classes will be added.

In case a string value is given, the classes will be (uniquely) merged with the classes defined in the `attr` option. Multiple classes must be separated by a space.

---

##### filter_external
**type:** `boolean` **default** `true`

Specifies whether any filters for any columns are either directly rendered inside the table header (option is `false`) or in a popover dialog (`true`).

<table class="table table-hover table-striped">
<thead>
<tr>
<th><code>filter_external</code> value <code>true</code></th>
<th><code>filter_external</code> value <code>false</code></th>
</tr>
</thead>
<tbody>
<tr>
<td>
<img src="data:image/png;base64,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" >
</td>
<td>
<img src="data:image/png;base64,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" />
</td>
</tr>
</tbody>
</table>

---

##### sort_on_header_label
**type:** `boolean` **default** `true`

Defines whether sorting a column is triggered by clicking the label in the table header (i.e. `true`) or anwyhere in the table header (`false`).

jQuery Datatable default behaviour is to trigger sorting of a column, when the header of the column is clicked. However, if there are line-breaks in the table header there may be a large blank area that responds to clicks and as such triggers a sorting which may not always be desirable. In this case, set the option to `true`.

---

##### column_groups
**type:** `array`, `null` **default** `null`

Columns may be grouped inside the column selector, contained in the table toolbar.

In case `null` is set, no groups will be added to the column selector and no columns will be grouped.

In case an array is set, it can either be a simple `key => value` pair array, where the key is the unique identifier for the group and the value is the label for the corresponding group:

```php
// ...

/** @var \StingerSoft\DatatableBundle\Table\Table $table */
$table = $service->createTable(MyTableType::class, $qb, array(
	'column_groups' => array(
		'group1' => 'My first label', 
		'group2' => 'My second label',
		'group3' => 'My third label'
	)
));
````

In this case, the [`translation_domain`](#translation-domain-for-table) of the table will be used for translating the groups labels.

If you need or want to use a different translation domain or do not want any translation at all, you can provide an array as a value, containing the two keys `label` and `translation_domain`:

```php
// ...

/** @var \StingerSoft\DatatableBundle\Table\Table $table */
$table = $service->createTable(MyTableType::class, $qb, array(
	'column_groups' => array(
		'group1' => array('label' => 'My first label', 'translation_domain' => false),
		'group2' => array('label' => 'My second label', 'translation_domain' => false),
		'group3' => array('label' => 'my.third.label', 'translation_domain' => 'StingerSoftDatatableBundle'),
	)
));
````

In order to assign a column to a specific group, set the columns [`column_group`](#column-group) option to a key in the array used for `column_groups` option.

_Please Note_: the order of the column groups in the resulting view is according to their definition in the table type. So the first column group defined in the `column_groups` array will be the first group in the column selector. Columns which have no column group defined, will be added after all groups in order of definition.

---

##### search_enabled
**type:** `boolean` **default** `true`

Allows to disable or enable the table-wide search filter.

---

##### search_placeholder
**type:** `string` **default** `null`

The placeholder to be used for the table-wide search filter (if any). If no specific placeholder is set (i.e. `null`) a default one will be used.

This placeholder may be translated using the [`translation_domain`](#translation-domain-for-table) option.

---

##### reload_enabled
**type:** `boolean` **default** `true`

If enabled (i.e. `true`) an extra button is added to the table toolbar, which allows to simply reload the table.

---

##### reload_tooltip
**type:** `string` **default** `null`

If a reload button is shown (see [`reload_enabled`](#reload-enabled)), the tooltip for that button can be specified with this option. If no specific tooltip is set (i.e. `null`) a default one will be used.

This tooltip may be translated using the [`translation_domain`](#translation-domain-for-table) option.

---

##### clear_enabled
**type:** `boolean` **default** `true`

If enabled (i.e. `true`) an extra button is added to the table toolbar, which allows to clear any filters (column specific and search) as well as to reset the table sorting.

---

##### clear_tooltip
**type:** `string` **default** `null`

If a clear button is shown (see [`clear_enabled`](#clear-enabled)), the tooltip for that button can be specified with this option. If no specific tooltip is set (i.e. `null`) a default one will be used.

This tooltip may be translated using the [`translation_domain`](#translation-domain-for-table) option.

---

##### state_save_key

**type:** `string` or `true` **default** `true`

Defines the general storage key to be used when persisting settings for the table.

In case `null` is given as the value for the option, the default behaviour of the jQuery Datatables will be applied for persistence. As such, the Id of the table and the URL the table is browsed on will be concatenated and used as the storage key.

In case `true` is given as the value for the option, only the Id of the table will be used as the storage key, regardless of the URL the table is browsed on.

Otherwise an arbitrary string can be provided in order to specify the key the settings will be persisted under. Using a specific key for certain tables allows "grouping" these tables together in terms of persistence.

_Please note_: There are individual keys for persistence of [ordering](#order-state-save-key), [filtering](#filter-state-save-key), [search](#search-state-save-key), [column visibility](#visibility-state-save-key) and [page length](#page-length-state-save-key) that allow more fine grained control.

**Related**: [`stateSave`](#stavesave) option to enable/disable persistence

**Related**: [`stateDuration`](#stateduration) option to define persistence target

---

##### search_state_save_key

**type:** `string`, `boolean` or `null` **default** `false`

Allows to define a separate persistence key for storing the table-wide search filter.

In case `null` is given, the table-wide search filter will not be treated separately in terms of persistence and instead the table-wide search filter will be persisted using the general [`state-save-key`](#state-save-key).

In case `false` is given, the table-wide search filter will never be persisted (default).

In case `true` is given, the table-wide search filter will be persisted under a global persistence key (`StingerSoftJQueryDataTable_search`), which all other tables with a value of `true` for the option `search_state_save_key` will use as well. As such, all tables with a value of `true` will use the same table-wide search filter and overwrite each others table-wide search accordingly.

Any other string value will set the persistence key to be used. Using a specific key for certain tables allows "grouping" these tables together in terms of persistence.

---

##### order_state_save_key

**type:** `string`, `boolean` or `null` **default** `null`

Allows to define a separate persistence key for ordering/sorting of table columns.

In case `null` is given, the ordering will not be treated separately in terms of persistence and instead the ordering will be persisted using the general [`state-save-key`](#state-save-key).

In case `false` is given, the ordering will never be persisted (default).

In case `true` is given, the ordering will be persisted under a global persistence key (`StingerSoftJQueryDataTable_order`), which all other tables with a value of `true` for the option `order_state_save_key` will use as well. As such, all tables with a value of `true` will use the same ordering and overwrite each others ordering accordingly.

Any other string value will set the persistence key to be used. Using a specific key for certain tables allows "grouping" these tables together in terms of persistence.

---

##### filter_state_save_key

**type:** `string`, `boolean` or `null` **default** `null`

Allows to define a separate persistence key for the column filters.

In case `null` is given, the column filters will not be treated separately in terms of persistence and instead the column filters will be persisted using the general [`state-save-key`](#state-save-key).

In case `false` is given, the column filters will never be persisted (default).

In case `true` is given, the column filters will be persisted under a global persistence key (`StingerSoftJQueryDataTable_filter`), which all other tables with a value of `true` for the option `filter_state_save_key` will use as well. As such, all tables with a value of `true` will use the column filters and overwrite each others column filters accordingly.

Any other string value will set the persistence key to be used. Using a specific key for certain tables allows "grouping" these tables together in terms of persistence.

---

##### visibility_state_save_key

**type:** `string`, `boolean` or `null` **default** `null`

Allows to define a separate persistence key for the visibility of columns.

In case `null` is given, the visibility of columns will not be treated separately in terms of persistence and instead the visibility of columns will be persisted using the general [`state-save-key`](#state-save-key).

In case `false` is given, the visibility of columns will never be persisted (default).

In case `true` is given, the visibility of columns will be persisted under a global persistence key (`StingerSoftJQueryDataTable_visibility`), which all other tables with a value of `true` for the option `filter_state_save_key` will use as well. As such, all tables with a value of `true` will have the same columns visible and overwrite each others visibility of columns accordingly.

Any other string value will set the persistence key to be used. Using a specific key for certain tables allows "grouping" these tables together in terms of persistence.

---

##### page_length_state_save_key

**type:** `string`, `boolean` or `null` **default** `null`

Allows to define a separate persistence key for storing the maximum number of rows to be show in a paginated table per page.

In case `null` is given, the maximum number of rows to be show in a paginated table per page will not be treated separately in terms of persistence and instead the value will be persisted using the general [`state-save-key`](#state-save-key).

In case `false` is given, the maximum number of rows to be show in a paginated table per page will never be persisted (default).

In case `true` is given, the maximum number of rows to be show in a paginated table per page will be persisted under a global persistence key (`StingerSoftJQueryDataTable_page_length`), which all other tables with a value of `true` for the option `page_length_state_save_key` will use as well. As such, all tables with a value of `true` will have the same maximum number of rows to be show in a paginated table per page and overwrite each limit accordingly.

Any other string value will set the persistence key to be used. Using a specific key for certain tables allows "grouping" these tables together in terms of persistence.

---

### Column Types
As a table is composed of columns, for each column a certain column type can be specified and the required options can be provided individually.

Below you find a list of all column types:
<p>
<table class="table table-hover table-striped">
  <thead>
    <tr>
      <th>Column Types</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td>
            <ul>
                <li><a href="#string-column-type"><code title="StingerSoft\DatatableBundle\Column\StringColumnType">StringColumnType</code></a></li>
                <li><a href="#integer-column-type"><code title="StingerSoft\DatatableBundle\Column\IntegerColumnType">IntegerColumnType</code></a></li>
                <li><a href="#formatted-string-column-type"><code title="StingerSoft\DatatableBundle\Column\FormattedStringColumnType">FormattedStringColumnType</code></a></li>
                <li><a href="#templated-column-type"><code title="StingerSoft\DatatableBundle\Column\TemplatedColumnType">TemplatedColumnType</code></a></li>
                <li><a href="#date-time-column-type"><code title="StingerSoft\DatatableBundle\Column\DateTimeColumnType">DateTimeColumnType</code></a></li>
                <li><a href="#moment-date-time-column-type"><code title="StingerSoft\DatatableBundle\Column\MomentDateTimeColumnType">MomentDateTimeColumnType</code></a></li>
                <li><a href="#count-column-type"><code title="StingerSoft\DatatableBundle\Column\CountColumnType">CountColumnType</code></a></li>
                <li><a href="#email-column-type"><code title="StingerSoft\DatatableBundle\Column\EmailColumnType">EmailColumnType</code></a></li>
                <li><a href="#yesno-column-type"><code title="StingerSoft\DatatableBundle\Column\YesNoColumnType">YesNoColumnType</code></a></li>
                <li><a href="#progress-bar-column-type"><code title="StingerSoft\DatatableBundle\Column\ProgressBarColumnType">ProgressBarColumnType</code></a></li>
                <li><a href="#async-child-row-trigger-column-type"><code title="StingerSoft\DatatableBundle\Column\AsyncChildRowTriggerColumnType">AsyncChildRowTriggerColumnType</code></a></li>
                <li><a href="#number-formatter-column-type"><code title="StingerSoft\DatatableBundle\Column\NumberFormatterColumnType">NumberFormatterColumnType</code></a></li>
                <li><a href="#currency-column-type"><code title="StingerSoft\DatatableBundle\Column\CurrencyColumnType">CurrencyColumnType</code></a></li>
            </ul>
        </td>
    </tr>
  </tbody>
</table>
</p>

#### Column Type Options & Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
  </tbody>
</table>

#### View Fields

##### path (for Column)
**type:** `string`

The path or alias under which the column is registered in the table and the path to get the value of the underlying object from.

---

##### template (for Column)
**type:** `string` **default** `'StingerSoftDatatableBundle:Column:column.js.twig'`

The twig template to be used for rendering the JSON object for the column, used by jQuery Datatables column definition.

---

#### Options

##### label (for Column)
**type:** `string` or `null` **default**: `''`

Sets the label for the column, which will be used in the table header.

In case `null` is given, no label will be printed at all.

In case an empty string (`''`) is given , the key under which the column is added to the table will be "humanized" and used as the label. If a column with the key `user.firstName` for instance is added to a table, the "humanized" label will be `User First Name`. For more info, see the [Symfony humanize twig filter](http://symfony.com/doc/3.2/reference/twig_reference.html#humanize).

In case a non-empty string is given, the label will be exactly that string which may be translated using the columns [`translation_domain`](#translation-domain-for-column) option.

_Please note_: the columns label can also be abbreviated by using the [`abbreviation_label`](#abbreviation_label) option. In case an abbreviation label is defined, the `label` option will be used as a tooltip or title for the abbreviation.

---

##### translation_domain (for Column)

**type:** `null`, `string`, `boolean` **default**: `null`

Defines the translation domain to be used when translating the columns label.

Setting a value of `false` results in no translation, whereas `true` means to re-use the currently set translation domain.

_Please note_: In case no translation domain is given (i.e. its value is `null`), the [translation domain of the table](#translation-domain-for-table) will be used instead.

---

##### abbreviation_label

**type:** `string` or `null` **default** `null`

Allows to specify an abbreviated label that will be used in the table header.

The abbreviation label allows to put an abbreviated string in the table header while also maintaining the original (long) label as a tooltip by setting the `label` option.

In case `null` is given (default), the default `label` will be used in the table header for the column.

In case a string value is given, this will be used as the label for the column in the table header and a tooltip / title will be added, using the `label` option value. The label may be translated using the [`abbreviation_translation_domain`](#abbreviation-translation-domain).

---

##### abbreviation_translation_domain

**type:** `string`, `boolean` **default** `true`

Defines the translation domain to be used when translating the columns abbreviation label.

Setting a value of `false` results in no translation, whereas `true` (default) means to use the same translation domain as defined for the column (i.e. [`translation_domain`](#translation-domain-for-column)).

---

##### orderSequence
**type:** `null` or `array` **default** `['asc', 'desc', '']`

Controls how ordering on the column shall be applied or cycled.

By default, jQuery Datatables allow sorting one a column first in ascending order then descending order and then the cycle starts again. Setting the `orderSequence` allows you to control the cycle or even prevent descending order.

In case `null` is given, the jQuery Datatables default order sequence of `['asc', 'desc']` will be used, which does not allow to reset ordering after sorting in descending order.

**Related**: _jQuery Datatable Column Option_ [`orderSequence`](https://datatables.net/reference/option/columns.orderSequence)

---

##### empty_value
**type:** `string` or `null` **default** `null`

Defines the value to be returned as the data for the rendered cell in case the underlying object to be rendered provides a `null` value.

If a table for instance shows a list of users and those users can have an address, which is joined via foreign keys, the address object may be null for certain users. In case the table should simply display nothing (i.e. `null`) you can ignore the `emtpy_value` option, but if a question mark shall be displayed instead, one can set the `empty_value`option for that specific column to exactly that, a `?`.

**Related**: see _jQuery Datatable Column Option_ [`render`](https://datatables.net/reference/option/columns.render#function)

---

##### searchable
**type:** `boolean` **default** `true`

Specifies whether a column is globally searchable or not.

If a column is marked as searchable, the table-wide search will also be applied on the particular table. Moreover, a column-specific filter can only be used if a column is searchable. In case you want to enable a specific filter control for this particular column, set the `filterable` and `filter_type` as well as `filter_options` options accordingly.

_Please Note_: If the `searchable` option is set to `false`, you cannot enable filtering for that particular column, using the `filterable` option.

**Related**: _jQuery Datatable Column Option_ [`searchable`](https://datatables.net/reference/option/columns.searchable)

---

##### filterable
**type:** `boolean` **default** `false`

Specifies whether a column is filterable by a specific filter control or not.

In order to define the filter control to be used and to provide options for that filter type, set the [`filter_type`](#filter-type) and [`filter_options`](#filter-options)

---

##### filter_type
**type:** `string`, `null` **default** `null` or `"StingerSoft\DatatableBundle\Filter\TextFilterType"`

Defines the type of filter to be used for the column where a type is identified by its class name.

In case `filterable` option is `true` and no filter type is defined (i.e. `null`), the default filter type [`TextFilterType`](#text-filter-type) will be used instead.

---

##### filter_options
**type:** `array` **default** `[]`

Defines any additional options required for the desired filter type for the column.

Options for the filter type may be passed as `key => value` array.

**Related**: [Filter Options](#filter-type-options) and the options specific for the selected filter type.

---

##### orderable
**type:** `boolean` **default** `true`

Controls whether the column can be sorted or ordered by the user.

**Related**: _jQuery Datatable Column Option_ [`orderable`](https://datatables.net/reference/option/columns.orderable)

---

##### route
**type:** `string`, `array`, `callable` or `null` **default** `null`

Allows to link the content of a table to a certain URL.

In case `null` is given, the content of the cell will not be wrapped in a hyperlink.

In case a string value is given, the value will be used as the URL for the hyperlink.

In case an array is given, there must be at least the key `route` existing, pointing to an available route which will be used for generating the URL for the hyperlink:

```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('user', StringColumnType::class, array(
		'route' => array(
			'route' => 'user_edit'
		),
		// ..
	));
}
```

In case any parameters are required for the route, the array may also contain a second key `route_params` with an array as its value, where `key => value` pairs are used to provide any parameters required for the route to be generated:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('settings', StringColumnType::class, array(
		'route' => array(
			'route' => 'platform_settings',
			'route_params' => array(
				'setting' => 'user-import',
				'edit' => true
			)
		),
		// ..
	));
}
```
Attributes to be added to the generated `a` HTML tag can be defined by adding an additional `attr` key, containing `key => value` pairs:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('settings', StringColumnType::class, array(
		'route' => array(
			'route' => 'platform_settings',
			'route_params' => array(
				'setting' => 'user-import',
				'edit' => true
			),
			'attr' => array(
				'class' => 'btn btn-default',
				'data-target' => '#ajax3'
				'data-toggle' => '#modal'
			)
		),
		// ..
	));
}
```

Additionally, a delegate or `callable` may be set for the `route` option, which is expected to return a string(i.e. URL)
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('delete', StringColumnType::class, array(
		'route' => function($item, $value, RouterInterface $router, $columnOptions) {
			return $router->generate('user_delete', array(
			    	'user-id' => $item->getId()), 
			    	UrlGeneratorInterface::ABSOLUTE_PATH);
		}, 
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$value` is the value to be used for the actual column, retrieved from the `$item` object using the columns key or `query_path` option.
+ `$router` is the Symfony Routing service which can be used for generating URLs
+ `$columnOptions` are the options defined for the column to generate the route for

_Please Note_: in case a non-empty value is defined for the `route` option, a data transformer (`\StingerSoft\DatatableBundle\Transformer\LinkDataTransformer`) will be automatically added to the column and then used during generation of the view / data to transform any URLs to hyperlinks.

---

##### query_path
**type:** `string` or `null` **default** `null`

Allows to specify a query path which will be used when filtering, searching or ordering is to be applied for the column.

Sometimes the underlying query builder of the table uses joins for foreign tables with a specific alias, which may be different from the actually selects and thus object fields or properties to be accessed when rendering a columns' value. In that case the path to be used for querying can be specified.

In case `null` is given, the columns key / name provided when adding the column to the table will be used as the path to be used for querying.

---

##### class_name
**type:** `string` or `null` **default** `null`

Allows adding CSS class names to every cell being rendered in this column.

In case `null` is given, no class names will be added to the HTML output by jQuery Datatables.

In case a string value is given, it will be passed as the class attribute to the table cell as is. As such, you can simply add multiple class by seperating them by spaces (i.e. `"class1 class2"`).

**Related**: _jQuery Datatable Column Option_ [`className`](https://datatables.net/reference/option/columns.className)

---

##### visible
**type:** `boolean` **default** `true`

Determines whether the column is initially visible (`true`) or not (`false`).

Initially means that a column may be toggleable and that table settings may be persist, which may result in an invisible column, because the user decided to hide it, even though the visible property is set to `true`.

**Related**: _jQuery Datatable Column Option_ [`visible`](https://datatables.net/reference/option/columns.visible)

----

##### toggleable
**type:** `boolean` **default** `true`

Determines whether the columns visibility can be toggled (`true`) or not (`false`).

In case a column selector is rendered above the table columns may be toggleable and as such can be displayed or hidden. If the column is marked as toggleable, the control (i.e. checkbox) for that column is enabled, otherwise it is disabled and the visibility state of the column cannot be changed by the user via the UI.

In case you want to completely hide the column from the column selector, consider using the [`toggle_visible`](#toggle-visible) option.

----

##### toggle_visible
**type:** `boolean` **default** `true`

Controls whether a column is listed in the column selector and thus can be toggled by the user via the UI.

----

##### column_group
**type:** `string` or `null` **default** `null`

Allows to add the column to a specific column group by its key, defined in the table option [`column_groups`](#column-groups).

---

##### search_server_delegate
**type:** `callable` or `null` **default** `null`

Allows to provide a custom function to be called when a table-wide search is executed on the table in order to adjust the query builder and add the relevant where clauses to it according to the columns' requirements:

```php
use Doctrine\ORM\QueryBuilder;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;
use StingerSoft\DatatableBundle\Column\Column;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('user', StringColumnType::class, array(
		'search_server_delegate' => function(QueryBuilder $queryBuilder, $parameterBindingName, $value, Column $column, $queryPath) {
			// we want to search for users whose username, firstname OR surname is LIKE the value to search for
			$expression = $queryBuilder->expr()->orX(
			    $queryBuilder->expr()->like('user.username', $parameterBindingName),
			    $queryBuilder->expr()->like('user.firstname', $parameterBindingName),
			    $queryBuilder->expr()->like('user.surname', $parameterBindingName)
			);
			// we HAVE to bind the parameter to the value in the delegate explicitly!
			$queryBuilder->setParameter($parameterBindingName, '%' . $value . '%');
			// and we return the OR expression
			return $expression;
		}, 
		// ..
	));
}
```

Ideally, the delegate returns a single `Doctrine\ORM\Query\Expr` expression (which may be a conjunction of multiple expressions by using `or` or `and` etc.) or an array of `Doctrine\ORM\Query\Expr` expressions. The expression(s) returned will be added to the query to be generated with the `andWhere` method.

In case `null` is returned, the delegate will simply be ignored and the column will not be searched.

The parameters passed to the delegate are the following:
<table class="table table-hover table-striped">
  <thead>
    <tr>
      <th>Parameter</th>
      <th>Type</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td><code>$queryBuilder</code></td>
        <td><code title="Doctrine\ORM\QueryBuilder">QueryBuilder</code></td>
        <td>The initial query builder of the table. This can be used to create expressions which will be added to the final search query. One can even add new joins etc. which may be crucial for a correct search result.</td>
    </tr>
    <tr>
        <td><code>$parameterBindingName</code></td>
        <td><code>string</code></td>
        <td>This is the name of the parameter binding to be used when actually setting a parameter for any expressions. The binding name is suffixed by a counter which is incremented for every searchable column. In case more or different parameters are needed, make sure to use unique binding names. Additionally, <b>you must bind any parameters within the delegate</b>!</td>
    </tr>
    <tr>
        <td><code>$value</code></td>
        <td><code>string</code></td>
        <td>The actual value to be searched for.</td>
    </tr>
    <tr>
        <td><code>$column</code></td>
        <td><code title="StingerSoft\DatatableBundle\Column\Column">Column</code></td>
        <td>The column object the search is executed on. This provides access to any filter or option etc. in case it is required for searching.</td>
    </tr>
    <tr>
        <td><code>$queryPath</code></td>
        <td><code>string</code></td>
        <td>The initial (query) path for the column.</td>
    </tr>
  </tbody>
</table>

---

##### filter_server_delegate
**type:** `callable` or `null` **default** `null`

Allows to provide a custom function to be called when the column shall be explicitly filtered in order to adjust the query builder and add the relevant where clauses to it according to the columns' requirements:

```php
use Doctrine\ORM\QueryBuilder;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\IntegerColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('user.id', IntegerColumnType::class, array(
		'filter_server_delegate' => function(QueryBuilder $queryBuilder, $filterValue, $filterRegex, $parameterBindingName, $queryPath, $filterTypeOptions) {
			$expression = null;
			// we don't handle regular expressions
            if($filterRegex === false) {
                // if a filter value is set, it must be equal to the users id!
                $expression = $queryBuilder->expr()->eq($queryPath, $parameterBindingName);

                // we HAVE to bind the parameter to the value in the delegate explicitly!
                $queryBuilder->setParameter($parameterBindingName, $filterValue);
            }
            return $expression;
        }, 
        // ..
    ));
}
```

Ideally, the delegate returns a single `Doctrine\ORM\Query\Expr` expression (which may be a conjunction of multiple expressions by using `or` or `and` etc.) or an array of `Doctrine\ORM\Query\Expr` expressions. The expression(s) returned will be added to the query to be generated with the `andWhere` method.

In case `null` is returned, the delegate will simply be ignored and the column will not be filtered.

The parameters passed to the delegate are the following:
<table class="table table-hover table-striped">
  <thead>
    <tr>
      <th>Parameter</th>
      <th>Type</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td><code>$queryBuilder</code></td>
        <td><code title="Doctrine\ORM\QueryBuilder">QueryBuilder</code></td>
        <td>The initial query builder of the table. This can be used to create expressions which will be added to the final search query. One can even add new joins etc. which may be crucial for a correct filter result.</td>
    </tr>
    <tr>
        <td><code>$parameterBindingName</code></td>
        <td><code>string</code></td>
        <td>This is the name of the parameter binding to be used when actually setting a parameter for any expressions. The binding name is suffixed by a counter which is incremented for every filterable column. In case more or different parameters are needed, make sure to use unique binding names. Additionally, <b>you must bind any parameters within the delegate</b>!</td>
    </tr>
    <tr>
        <td><code>$filterValue</code></td>
        <td><code>string</code> or <code>string[]</code></td>
        <td>The actual value to be searched for. If a filter allows to select multiple values, an array is given.</td>
    </tr>
    <tr>
        <td><code>$filterRegex</code></td>
        <td><code>boolean</code></td>
        <td><code>true</code> in case the filter uses regular expression, <code>false</code> otherwise</td>
    </tr>
    <tr>
        <td><code>$queryPath</code></td>
        <td><code>string</code></td>
        <td>The initial (query) path for the column.</td>
    </tr>
    <tr>
        <td><code>$filterTypeOptions</code></td>
        <td><code>array</code></td>
        <td>The options for the filter type defined for the column (see <a href='#filter-type-options'></a>).</td>
    </tr>
  </tbody>
</table>

---

##### order_server_delegate
**type:** `callable` or `null` **default** `null`

Allows to provide a custom function to be called when the column shall be ordered:

```php
use Doctrine\ORM\QueryBuilder;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;
use StingerSoft\DatatableBundle\Column\Column; 

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('user', StringColumnType::class, array(
		'order_server_delegate' => function($direction, QueryBuilder $queryBuilder, Column $column, $queryPath, $rootAlias) {
			return array(
				$rootAlias.'.lastname' => $direction,
				$rootAlias.'.firstname' => $direction
			);
        }, 
        // ..
    ));
}
```

The delegate must return either `null` or an array with `key => value` pairs, where each `key` is a query path and the value either `asc` for ascending or `desc` for descending order.

In case `null` is returned, the delegate will simply be ignored and the column will not be filtered.

The parameters passed to the delegate are the following:
<table class="table table-hover table-striped">
  <thead>
    <tr>
      <th>Parameter</th>
      <th>Type</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td><code>$direction</code></td>
        <td><code>string</code></td>
        <td>The direction of the ordering to be applied to the column, either `asc` for ascending ordering or `desc` for descending ordering.</td>
    </tr>
    <tr>
        <td><code>$queryBuilder</code></td>
        <td><code title="Doctrine\ORM\QueryBuilder">QueryBuilder</code></td>
        <td>The initial query builder of the table. This can be used to create expressions or even add new joins etc. which may be crucial for a correct ordering.</td>
    </tr>
    <tr>
        <td><code>$column</code></td>
        <td><code title="StingerSoft\DatatableBundle\Column\Column">Column</code></td>
        <td>The column object the ordering is executed on. This provides access to any filter or option etc. in case it is required for ordering.</td>
    </tr>
    <tr>
        <td><code>$queryPath</code></td>
        <td><code>string</code></td>
        <td>The initial (query) path for the column.</td>
    </tr>
    <tr>
        <td><code>$rootAlias</code></td>
        <td><code>string</code></td>
        <td>The (query) path or alias of the root entity of the underlying query builder.</td>
    </tr>
  </tbody>
</table>

---

##### value_delegate
**type:** `callable` or `null` **default** `null`

Defines the function to be used for retrieving the actual value or object to be used for rendering the content of the column:

```php
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\StringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
    $builder->add('net', StringColumnType::class, array(
        'value_delegate' => function($item, $path, $options) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            try {
                // we get the net value from the object under path 'net'
                $netValue = $propertyAccessor->getValue($item, $path);
                // instead of the net value we return the gross value
                return round($netValue * 1.19, 2);
            } catch (UnexpectedTypeException $e) {
                return null;
            }
        }, 
        // ..
    ));
}
```

The parameters passed to the delegate are the following:
<table class="table table-hover table-striped">
  <thead>
    <tr>
      <th>Parameter</th>
      <th>Type</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td><code>$item</code></td>
        <td><code>object</code></td>
        <td>An instance of the object type that is returned by the underlying query builder (i.e. an instance of the type of items to be rendered in the table).</td>
    </tr>
    <tr>
        <td><code>$path</code></td>
        <td><code>string</code></td>
        <td>The path or property of the object to initially retrieve the value from.</td>
    </tr>
    <tr>
        <td><code>$options</code></td>
        <td><code>array</code></td>
        <td>The options of the column type.</td>
    </tr>
  </tbody>
</table>

The delegate may return any type of value, such as strings, integers etc. depending on the template used for generating the actual data for the jQuery datatable. In case `null` is returned, setting the [`empty_value`](#empty-value) option should be considered.

In case `null` is given, a default function will be executed, which tries to access the property defined by the columns' path on the row-specific object returned by the query of the table. If for instance a column with at path or key of `user.id` is added to the table and no `value_delegate` is specified, a [property accessor](http://symfony.com/doc/3.2/components/property_access.html) is used for retrieving a value.

---

### String Column Type

Render a string value in a table cell.

#### String Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\StringColumnType</code></td>
    </tr>
  </tbody>
</table>

### Integer Column Type

Renders an integer value in a table cell.

#### Integer Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\StringColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\IntegerColumnType</code></td>
    </tr>
  </tbody>
</table>

### Formatted String Column Type

Renders a formatted string value with a custom pattern in a table cell.

The actual formatting of the string is done by using the `\StingerSoft\DatatableBundle\Transformer\StringFormatterDataTransformer` data transformer that is automatically added to all columns of the `FormattedStringColumnType`.

#### Formatted String Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#string-format"><code>string_format</code></a></li>
                <li><a href="#string-format-paramaters"><code>string_format_parameters</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\FormattedStringColumnType</code></td>
    </tr>
  </tbody>
</table>

##### string_format
**type:**  `string` or `callable` **default** `"%s"`

Allows to specify the format to be used for producing a formatted string output.

In case a string value is given, it must be following the syntax of [PHPs sprintf function](http://php.net/manual/en/function.sprintf.php).

In case a `callable` delegate is given, it must return a string that follows the syntax of [PHPs sprintf function](http://php.net/manual/en/function.sprintf.php):

```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\FormattedStringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('deprecated', FormattedStringColumnType::class, array(
		'string_format' => function($item, $value, $path) {
			return ($value ? '%s, by %s on %s' : '%s');
		},
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$value` is the value to be used for the actual column, retrieved from the `$item` object using the columns key.
+ `$path` is columns key or the objects path to retrieve the value from.

##### string_format_parameters
**type:** `null`, `array` or `callable` **default** `null`

Allows to pass any additional parameters to the formatted string, besides the original cells' item value.

In case `null` is given, only the actual value of the cell is used to render a string according to the defined [`string_format`](#string-format).

In case a `callable` delegate is given, it must return an array with values to be used as parameters for the [`string_format`](#string-format):

```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\FormattedStringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('deprecated', FormattedStringColumnType::class, array(
		'string_format_parameters' => function($item, $value, $path) {
			if($value) {
				return array(
					'yes',
					$item->getDeprecationUser()->getUsername(),
					$item->getDeprecationDate()->__toString(),
				);
			} else {
				return array('no');
			}
		},
		// ..
	));
}
```

The parameters passed to the function are:
+ `$item` which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$value` is the value to be used for the actual column, retrieved from the `$item` object using the columns key.
+ `$path` is columns key or the objects path to retrieve the value from.

In case an `array` is given, the value type of the entries in the array can be a [simple scalar type](http://php.net/manual/en/function.is-scalar.php) or a `callable`. In case an entry value is callable, the delegate will receive the same parameters (`$item, $value, $path`) as above, but the delegate itself must return a scalar value:

```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\FormattedStringColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('deprecated', FormattedStringColumnType::class, array(
    		'string_format_parameters' => array(
                'yes',
                function($item, $value, $path) {
                    return $item->getTitle();
                }
            ),
    		// ..
    	));
}
```

### Templated Column Type

Renders a cell by using a custom twig template and passing item and value to be rendered.

#### Templated Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#template-option"><code>template</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>
            <ul>
                <li><a href="#value-delegate"><code>value_delegate</code></a> to allow rendering of a custom template</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\TemplatedColumnType</code></td>
    </tr>
  </tbody>
</table>

##### template (Option)
**type:** `string` **default** `null`

Defines the path to the template to be used when rendering the cells value.

The following variables will be passed to the template for rendering the value:
+ `item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `path` (string) is the columns key or the objects path to retrieve the value from.
+ `options` (array) the options defined for the column, including `translation_domain` or `label` for instance.

---

### Date Time Column Type

Renders a date/time value.

#### Date Time Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>
            <ul>
                <li><a href="#template-field-overwritten-for-datetime-column"><code>template</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\DateTimeColumnType</code></td>
    </tr>
  </tbody>
</table>

##### template (field, overwritten for DateTime Column)
**type:** `string` **default** `'StingerSoftDatatableBundle:Column:datetime.json.twig'`

The twig template to be used for rendering the JSON object for the column, used by jQuery Datatables column definition.

### Moment Date Time Column Type

Renders a date/time value in a cell using [MomentJS](https://momentjs.com).

#### Moment Date Time Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#date-format"><code>date_format</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>
            <ul>
                <li><a href="#template-field-overwritten-for-momentdatetime-column"><code>template</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\MomentDateTimeColumnType</code></td>
    </tr>
  </tbody>
</table>

##### template (field, overwritten for MomentDateTime column)
**type:** `string` **default** `'StingerSoftDatatableBundle:Column:datetime_moment.js.twig'`

The twig template to be used for rendering the JSON object for the column, used by jQuery Datatables column definition.

##### date_format
**type:** `string` **default** `'L LTS'`

The [format to be used by MomentJS](https://momentjs.com/docs/#/displaying/format/) for rendering the date/time value of the cell.

---

### Count Column Type

Renders the number of elements contained in a traversable or iterable value of an object as a cell value.

In order to use the column type, the object value or item should be an array or `Countable` object (i.e. suitable for [PHPs count method](http://php.net/manual/en/function.count.php)).

#### Count Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>
            <ul>
                <li><a href="#value-delegate"><code>value_delegate</code></a> to allow returning the count of the items value</li>
            </ul>
        </td>
    </tr>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\IntegerColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\CountColumnType</code></td>
    </tr>
  </tbody>
</table>

### Email Column Type

Renders a the cells value as a linked e-Mail address, using the `mailto:` protocol.

#### Email Column Type Options & View Fields


<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>
            <ul>
                <li><a href="#route"><code>route</code></a> to allow returning the value prefixed by a `mailto:`</li>
            </ul>
        </td>
    </tr>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\EmailColumnType</code></td>
    </tr>
  </tbody>
</table>

### Yes/No Column Type

Renders `'yes'` or `'no'` for boolean values in a cell.

The value for object to be rendered in the cell should be evaluable as `boolean`.

#### Yes/No Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#yes-label"><code>yes_label</code></a></li>
                <li><a href="#no-label"><code>no_label</code></a></li>
                <li><a href="#label-translation-domain"><code>label_translation_domain</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>
            <ul>
                <li><a href="#template-field-overwritten-for-yesno-column"><code>template</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\YesNoColumnType</code></td>
    </tr>
  </tbody>
</table>

##### template (field, overwritten for YesNo Column)
**type:** `string` **default** `'StingerSoftDatatableBundle:Column:yesno.js.twig'`

The twig template to be used for rendering the JSON object for the column, used by jQuery Datatables column definition.

---

##### label_translation_domain
**type:** `string`, `null` or `false` **default** `StingerSoftDatatableBundle`

The translation domain to be used for translating the `yes_label` and `no_label`.

In case `null` is given, the columns `translation_domain` option is used.

In case `false` is given, the `yes_label` and `no_label` options will not be translated at all.

---

##### yes_label
**type:** `string` **default** `stinger_soft_datatables.column_types.yes_no.yes`

The label to be used for `yes` values. This may be translated using the [`translation_domain`](#translation-domain-overwritten-for-yesno-column)

---

##### no_label
**type:** `string` **default** `stinger_soft_datatables.column_types.yes_no.no`

The label to be used for `no` values. This may be translated using the [`translation_domain`](#translation-domain-overwritten-for-yesno-column)

---

### Progress Bar Column Type

Renders a progress bar in the table cell according to the items value.

### Progress Bar Column Type Options & View Fields


<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#min"><code>min</code></a></li>
                <li><a href="#max"><code>max</code></a></li>
                <li><a href="#striped"><code>striped</code></a></li>
                <li><a href="#animated"><code>animated</code></a></li>
                <li><a href="#additional-classes"><code>additional_classes</code></a></li>
                <li><a href="#show-progress"><code>show_progress</code></a></li>
                <li><a href="#progress"><code>progress</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>
            <ul>
                <li><a href="#value-delegate"><code>value_delegate</code></a> to allow rendering a custom template (`StingerSoftDatatableBundle:Column:progress_bar.html.twig`) for the progress bar</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\ProgressBarColumnType</code></td>
    </tr>
  </tbody>
</table>

##### min
**type:** `numeric` or `callable` **default** `0`

Defines the minimum value for the progress bar.

This value will only be used as the [`aria-valuemin`](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/ARIA_Techniques/Using_the_aria-valuemin_attribute) attribute for the progress bar.

In case `callable` is given, it is expected to return a numeric value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'min' => function($item, $path, $options) {
			return $item->getStatus() == 1 ? 0 : 20;
		},
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

---

##### max
**type:** `numeric` or `callable` **default** `100`

Defines the maximum value for the progress bar.

This value will only be used as the [`aria-valuemax`](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/ARIA_Techniques/Using_the_aria-valuemax_attribute) attribute for the progress bar.

In case `callable` is given, it is expected to return a numeric value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'max' => function($item, $path, $options) {
			return $item->getStatus() == 0 ? 50 : 100;
		},
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

---

##### striped
**type:** `boolean` or `callable` **default** `false`

Defines whether the progress bar is striped or not. See [Bootstrap 3.x progress bars](http://getbootstrap.com/components/#progress-striped) for more info.

In case `callable` is given, it is expected to return a boolean value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'striped' => function($item, $path, $options) {
			return $item->isImportant() == 0 ? true : false;
		},
		// ..
	));
}
```

The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

---

##### animated
**type:** `boolean` or `callable` **default** `false`

Defines whether the progress bar is animated or not. See [Bootstrap 3.x progress bars](http://getbootstrap.com/components/#progress-animated) for more info.

In case `callable` is given, it is expected to return a boolean value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'animated' => function($item, $path, $options) {
			return $item->isInProgress() == 0 ? true : false;
		},
		// ..
	));
}
```

The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

_Please Note_: even though it is possible to enable the `animated` option it will only be visible if the `striped` is enabled as well.

---

##### additional_classes
**type:** `null`, `string` or `callable` **default** `null`

Allows to specify any additional classes to be added to the progress bar, besides the classes added automatically by enabling the `striped` and `animated` options.

In case `callable` is given, it is expected to return a string value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'additional_classes' => function($item, $path, $options) {
			return $item->isFinished() ? 'finished' : '';
		},
		// ..
	));
}
```

The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

_Please note_: in order to add several classes simply separate them by spaces.

---

##### show_progress
**type:** `boolean` or `callable` **default** `true`

Specifies whether the progress shall be printed on the progress bar in percent or not.

In case a `callable`is given, it is expected to return a boolean value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'show_progress' => function($item, $path, $options) {
			return ($item->getProgress() > 0) ? true : false;
		},
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.


---

##### progress
**type:** `null`, `numeric` or `callable` **default** `null`

Allows to define the actual progress of the progress bar.

In case `null` is given, the value of the property, identified by the columns key or path will be used as the progress value. The value is expected to be expressed in percent, as a value between 0 and 100.

In case a numeric value is given, this value will be used for the actual progress. The value is expected to be expressed in percent, as a value between 0 and 100.

In case a `callable` is given, it is expected to return a numeric value between 0 and 100:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\ProgressBarColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('progress', ProgressBarColumnType::class, array(
		'progress' => function($item, $path, $options) {
			return ($item->getStatus() == 1) ? 100 : $item->getStep();
		},
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

---

### Number Formatter Column Type

Renders a formatted numeric value with a custom pattern and style in a table cell, using PHPs number formatter capabilities.

The actual formatting of the value is done by using the `\StingerSoft\DatatableBundle\Transformer\NumberFormatterDataTransformer` data transformer that is automatically added to all columns of the type `NumberFormatterColumnType`.

#### Number Formatter Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#number-formatter-style"><code>number_formatter_style</code></a></li>
                <li><a href="#number-formatter-pattern"><code>number_formatter_pattern</code></a></li>
                <li><a href="#number-formatter-locale"><code>number_formatter_locale</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\NumberFormatterColumnType</code></td>
    </tr>
  </tbody>
</table>

##### number_formatter_style
**type:** `integer` **default** `\NumberFormatter::DEFAULT_STYLE`

Defines the style to be used when formatting numbers.

The option relates directly to the supported styles of PHPs `\NumberFormatter` and as such, there is [a list of allowed values for the style attribute](http://php.net/manual/en/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle).

---

##### number_formatter_pattern
**type:** `string` or `null` **default** `null`

Pattern string if the chosen style requires a pattern.

See [ICU 59.1: icu::DecimalFormat Class Reference](http://www.icu-project.org/apiref/icu4c/classDecimalFormat.html#details) for details on the syntax for the pattern.

---

##### number_formatter_locale
**type:** `string` or `null` **default** `null`

The locale to be used for formatting the number.

In case `null` is given, the default locale is used (see `\Locale::getDefault()`)

In case a `string` value is given, it is used as the locale.

---

### Currency Column Type

Renders a numeric value formatted as a currency with a custom pattern and style in a table cell, using PHPs number formatter capabilities.

The actual formatting of the value is done by using the `\StingerSoft\DatatableBundle\Transformer\CurrencyFormatterDataTransformer` data transformer that is automatically added to all columns of the type `CurrencyColumnType`.

#### Currency Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
                <li><a href="#number-formatter-style"><code>number_formatter_style</code></a></li>
                <li><a href="#number-formatter-pattern"><code>number_formatter_pattern</code></a></li>
                <li><a href="#number-formatter-locale"><code>number_formatter_locale</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul><li><a href="#currency"><code>currency</code></a></li></ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td><ul><li><a href="#number-formatter-style"><code>number_formatter_style</code></a> set to `\NumberFormatter::CURRENCY`</li></ul></td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\NumberFormatterColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\CurrencyColumnType</code></td>
    </tr>
  </tbody>
</table>

##### currency
**type:** `string` or `callable` **default** `'EUR'`

The currency to be used when formatting the value.

The general formatting of the currency can be influenced by setting the [`number_formatter_locale`](#number-formatter-locale) option.

In case a `string` value is given, it must be a 3-letter [ISO 4217 currency code](http://www.xe.com/iso4217.php).

In case a `callable` or delegate is given, it is expected to return a 3-letter ISO 4217 currency code:

```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\CurrencyColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
	$builder->add('costs', CurrencyColumnType::class, array(
		'currency' => function($item, $path, $options) {
			return $item->getCurrency() ? $item->getCurrency() : 'EUR';
		},
		// ..
	));
}
```
The parameters passed to the function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

---

### Child Row Trigger Column Type

Renders a control (by default a plus/minus icon) that allows to trigger a details row just below the row containing the trigger.

This column type serves as the base parent column type to be used by specific child row trigger column types, such as the [Async Child Row Trigger Column Type](#async-child-row-trigger-column-type)

#### Child Row Trigger Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="details-trigger-selector">details_trigger_selector</a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>
            <ul>
                <li><a href="#label"><code>label</code></a> defaults to `null`</li>
                <li><a href="#filterable"><code>filterable</code></a> defaults to `false` as the column should not be filterable</li>
                <li><a href="#orderable"><code>orderable</code></a> defaults to `false` as the column should not be orderable</li>
                <li><a href="#toggleable"><code>toggleable</code></a> defaults to `false` as the column should not be toggable</li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a> defaults to `false` as the column should not be listed in the column selector</li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a> defaults to `null` as the column should not be searchable</li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a> defaults to `null` as the column should not be filterable</li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a> defaults to `null` as the column should not be orderable</li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\ChildRowTriggerColumnType</code></td>
    </tr>
  </tbody>
</table>

##### details_trigger_selector
**type:** `string` **default** `'.table-childrow-expander'`

Specifies the [jQuery selector](https://api.jquery.com/category/selectors/) that will be used to identify actual detail triggers, rendered by the specific column type extending this column type.

To the elements matching the given selector, a Javascript `click` listener will be added, which will take care of showing or hiding the details.

---

### Async Child Row Trigger Column Type

Renders a control (by default a plus/minus icon) that allows to trigger a details row just below the row containing the trigger.

The content of the details row is loaded in an asynchronous way via ajax whenever necessary.

#### Async Child Row Trigger Column Type Options & View Fields

<table class="table table-hover table-striped">
  <tbody>
    <tr>
        <td>Inherited Options</td>
        <td>
            <ul>
                <li><a href="#label-for-column"><code>label</code></a></li>
                <li><a href="#translation-domain-for-column"><code>translation_domain</code></a></li>
                <li><a href="#abbreviation-label"><code>abbreviation_label</code></a></li>
                <li><a href="#abbreviation-translation-domain"><code>abbreviation_translation_domain</code></a></li>
                <li><a href="#ordersequence"><code>orderSequence</code></a></li>
                <li><a href="#empty-value"><code>empty_value</code></a></li>
                <li><a href="#searchable"><code>searchable</code></a></li>
                <li><a href="#filterable"><code>filterable</code></a></li>
                <li><a href="#filter-type"><code>filter_type</code></a></li>
                <li><a href="#filter-options"><code>filter_options</code></a></li>
                <li><a href="#orderable"><code>orderable</code></a></li>
                <li><a href="#route"><code>route</code></a></li>
                <li><a href="#query-path"><code>query_path</code></a></li>
                <li><a href="#class-name"><code>class_name</code></a></li>
                <li><a href="#visible"><code>visible</code></a></li>
                <li><a href="#toggleable"><code>toggleable</code></a></li>
                <li><a href="#toggle-visible"><code>toggle_visible</code></a></li>
                <li><a href="#column-group"><code>column_group</code></a></li>
                <li><a href="#search-server-delegate"><code>search_server_delegate</code></a></li>
                <li><a href="#filter-server-delegate"><code>filter_server_delegate</code></a></li>
                <li><a href="#order-server-delegate"><code>order_server_delegate</code></a></li>
                <li><a href="#value-delegate"><code>value_delegate</code></a></li>
                <li><a href="#details-trigger-selector"><code>details_trigger_selector</code></a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Options</td>
        <td>
            <ul>
                <li><a href="#child-container-template">child_container_template</a></li>
                <li><a href="#detail-route">detail_route</a></li>
                <li><a href="#refresh">refresh</a></li>
                <li><a href="#trigger-visible">trigger_visible</a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Overwritten Options</td>
        <td>
            <ul>
                <li><a href="#value-delegate"><code>value_delegate</code></a> in order to render another template containing the toggle</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td>Inherited Fields</td>
        <td>
            <ul>
                <li><a href="#path-for-column"><code>path</code></a></li>
                <li><a href="#template-for-column"><code>template</code></a></li>
            </ul>
         </td>
    </tr>
    <tr>
        <td>Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Overwritten Fields</td>
        <td>none</td>
    </tr>
    <tr>
        <td>Parent type</td>
        <td><code>StingerSoft\DatatableBundle\Column\ChildRowTriggerColumnType</code></td>
    </tr>
    <tr>
        <td>Class</td>
        <td><code>StingerSoft\DatatableBundle\Column\AsyncChildRowTriggerColumnType</code></td>
    </tr>
  </tbody>
</table>

##### child_container_template
**type:** `string` **default** `'StingerSoftDatatableBundle:Column:async_childrow.html.twig'`

Defines the template to be used for rendering the actual content of the cell (i.e. the trigger):

The default template renders [FontAwesome](http://fontawesome.io/icons/) icons ('+' when details are hidden, '-' when details are visible).

The following variables are passed to template to be rendered:
+ `item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `path` (string) is columns key or the objects path to retrieve the value from.
+ `url` (string) the URL of the content to be loaded asynchronously (see [`detail_route`](#detail-route) option)
+ `refresh` (boolean) `true` in case the content should be loaded every time the details are shown, `false` otherwise (see [`refresh`](#refresh) option)
+ `visible` (boolean) `true` in case the trigger shall be visible and clickable by the user, `false` otherwise (see [`details_visible`](#details-visible) option)

---

##### detail_route
**type:** `string`, `array` or `callable` **default** `null`

Defines the route to be used for loading details in an asynchronous manner.

In case a `string` value is given, it is interpreted as an (absolute) URL when loading the content.

In case an `array` is given, it must have at least the key `route` defined which should point to an existing route or a `callable` delegate returning a route name.
Additionally, a second key `route_params` may be specified, which can either an `array` with `key => value` pairs or a `callable` delegate, returning an `array` with `key => value` pairs. These pairs will then be used for generating a URL out of the route and the given parameters:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\AsyncChildRowTriggerColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
    $builder->add('trigger', AsyncChildRowTriggerColumnType::class, array(
            'detail_route' => array(
                'route' => 'acme_demo_details',
                'route_params' => function($item, $path, $options) {
                    return array('item' => $item->getId());
                }
            ),
            // ..
        ));
}
```
The parameters passed to the delegate functions are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

In case a callable `delegate` is given it must return an (absolute) url to be used or `null` to not render the trigger:
```
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\AsyncChildRowTriggerColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
    $builder->add('trigger', AsyncChildRowTriggerColumnType::class, array(
            'detail_route' => function($item, $path, $options, $router) {
                if($item->showDetails()) {
                    return $router->generate('acme_demo_details', array('id' => $item->getId()));
                }
                return null;
            },
            // ..
        ));
}
```

The parameters passed to the delegate function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.
+ `$router` (`\Symfony\Component\Routing\RoutingInterface`) the router to be used for generating routes

---

##### refresh
**type:** `boolean` or `callable` **default** `false`

Allows to specify whether the content of the details row must be refreshed upon every trigger.

In case a `callable` is given, it must return a `boolean` value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\AsyncChildRowTriggerColumnType;

public function buildTable(TableBuilderInterface $builder, array $options) {
    $builder->add('trigger', AsyncChildRowTriggerColumnType::class, array(
            'refresh' => function($item, $path, $options) {
                return $item->isFinished();
            },
            // ..
        ));
}
```
The parameters passed to the delegate function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

---

##### trigger_visible
**type:** `boolean` or `callable` **default** `true`

Allows to specify whether the trigger of the details row shall be visible or not.

In case a `callable` is given, it must return a `boolean` value:
```php
use StingerSoft\DatatableBundle\Service\TableBuilderInterface;
use StingerSoft\DatatableBundle\Column\AsyncChildRowTriggerColumnType;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
    
public function buildTable(TableBuilderInterface $builder, array $options) {
    /** @var AuthorizationChecker $authChecker  */
    $builder->add('trigger', AsyncChildRowTriggerColumnType::class, array(
            'trigger_visible' => function($item, $path, $options) use ($authChecker) {
                return $authChecker->isGranted('item-view-details', $item);
            },
            // ..
        ));
}
```
The parameters passed to the delegate function are:
+ `$item` (object) which is the object returned by the underlying table query builder (i.e. the object of the row to render the column for).
+ `$path` (string) is columns key or the objects path to retrieve the value from.
+ `$options` (string) is the array containing all options for the column, such as `translation_domain` for instance.

### Filter Types

#### Filter Type Options

## Requirements
* PHP >= 5.6
* pec-resources/datatables >= 1.10.13
* pec-resources/datatables-scroller >= 1.4.2
* stinger/moment-js-bundle >= 2.10
* symfony/framework-bundle >= 2.7

## License
This file is part of the PEC Platform Data Table Bundle. For the full copyright and license information, please view the LICENSE file that was distributed with this bundle. 

(c) PEC project engineers & consultants