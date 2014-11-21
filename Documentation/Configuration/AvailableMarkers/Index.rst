.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _configuration-markers:

List of available markers
^^^^^^^^^^^^^^^^^^^^^^^^^


.. _configuration-content-markers:

Content markers
"""""""""""""""


.. _configuration-content-markers-field:

FIELD
~~~~~

Name
  ###FIELD.myField###

Description
  This is the most common marker that deals with content of the
  database. When possible, try to make correspond the name of the marker
  with the name of the field. Templatedisplay will be able to guess
  automatically the mapping. Click on it to start the mapping process.


.. _configuration-content-markers-object:

OBJECT
~~~~~~

Name
  ###OBJECT.myValue###

Description
  Attach some TypoScript to this marker. Same configuration options than
  FIELD markers but no field associated with.


.. _configuration-content-markers-label:

LABEL
~~~~~

Name
  ###LABEL.myField###

Description
  The label of the field is translated according to the language of the
  website. To have a correct translation, the LABEL must have a proper
  TCA.


.. _configuration-content-markers-lll:

LLL
~~~

Name
  ###LLL:EXT:myExtension/locallang.xml:myKey###

Description
  When no TCA is provided or an external string must be translated, use
  this syntax for translating a chain of character.


.. _configuration-content-markers-expression:

EXPRESSION
~~~~~~~~~~

Name
  ###EXPRESSION.key:var1\|var2###

Description
  Calls on the expression parser of extension “expressions” to resolve
  any well-formed expression.

  **Example:**

  .. code-block:: typoscript

		###EXPRESSION.gp:clear_cache###

  will retrieve the value of a GET/POST variable called “clear\_cache”.


.. _configuration-content-markers-filter:

FILTER
~~~~~~

Name
  ###FILTER.myTable.myField###

Description
  Value of a filter. MyTable is optional and depend of the filter
  naming.


.. _configuration-content-markers-sort-sort:

SORT.sort
~~~~~~~~~

Name
  ###SORT.sort###

Description
  Value of the sort. The most probably a field name.


.. _configuration-content-markers-sort-order:

SORT.order
~~~~~~~~~~

Name
  ###SORT.order###

Description
  Value of the order. Can be "ASC" or "DESC"


.. _configuration-content-markers-session:

SESSION
~~~~~~~

Name
  ###SESSION.sessionName.order###

Description
  Access information stored in the session.


.. _configuration-content-markers-counter:

COUNTER
~~~~~~~

Name
  ###COUNTER###

Description
  The counter is automatically incremented by 1. This syntax makes sense
  inside a LOOP and can be used for styling odd / even rows of a table
  for example. The syntax may looks like this:

  .. code-block:: html

	<!--IF(###COUNTER### % 2 == 0)-->class="even"<!--ELSE-->class="odd"<!--ENDIF-->

  In the case of a LOOP in a LOOP the second COUNTER remains
  independent.

  .. code-block:: html

		<!--LOOP(pages)-->
			<div>counter 1 : ###COUNTER###</div>
			<!--LOOP(tt_content)-->
				<div>counter 2 : ###COUNTER###</div>
			<!--ENDLOOP-->
		<!--ENDLOOP-->


.. _configuration-content-markers-counter-loop-name:

COUNTER(loop\_name)
~~~~~~~~~~~~~~~~~~~

Name
  ###COUNTER(loop\_name)###

Description
  This kind of counter is handy in case of LOOP in a LOOP. Let's assume,
  we need to access the value of the parent COUNTER in a child's LOOP.

  .. code-block:: html

		<!--LOOP(pages)-->
			<div>Some value</div>
			<!--LOOP(tt_content)-->
				<div>counter pages: ###COUNTER(pages)###</div>
			<!--ENDLOOP-->
		<!--ENDLOOP-->


.. _configuration-content-markers-page-browser:

PAGE\_BROWSER
~~~~~~~~~~~~~

Name
  ###PAGE\_BROWSER###

Description
  If extension "pagebrowse" is installed and correctly loaded, displays
  a universal page browser. Other page browsers are possible but must be
  handled with a hook.


.. _configuration-content-markers-record:

RECORD
~~~~~~

Name
  ###RECORD(tt\_content, 12)###

Description
  Call in the template it self an external record. Very handy for
  including records in a records.

  If using a FAL file id as a template reference (see above), the
  records pointed to using this marker will be properly recorded in
  references (sys\_reference).


.. _configuration-content-markers-hook:

HOOK
~~~~

Name
  ###HOOK.myHook###

Description
  :ref:`See section about hooks <developers-hooks>`


.. _configuration-content-markers-total-records:

TOTAL\_RECORDS
~~~~~~~~~~~~~~

Name
  ###TOTAL\_RECORDS###

Description
  Returns the total number in the main of records  **without considering
  a possible limit** . To have a glimpse on the data structure, add the
  parameter "debug[structure]" in the URL. The value
  ###TOTAL\_RECORDS### corresponds to the cell "**totalCount**" of the
  main structure (level 1). Make sure you have a backend login to see
  the table.


.. _configuration-content-markers-subtotal-records:

SUBTOTAL\_RECORDS
~~~~~~~~~~~~~~~~~

Name
  ###SUBTOTAL\_RECORDS###

Description
  Returns the total of records in the main data structure **considering
  a possible limit** . To have a glimpse on the data structure, add the
  parameter "debug[structure]" in the URL. The value
  ###SUBTOTAL\_RECORDS### corresponds to the cell "**count**" of the
  main structure (level 1). Make sure you have a backend login to see
  the table.


.. _configuration-content-markers-total-records-tablename:

TOTAL\_RECORDS(tablename)
~~~~~~~~~~~~~~~~~~~~~~~~~

Name
  ###TOTAL\_RECORDS(tablename)###

Description
  Returns the total of records corresponding to a table name **without
  considering a possible limit** .


.. _configuration-content-markers-subtotal-records-tablename:

SUBTOTAL\_RECORDS(tablename)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Name
  ###SUBTOTAL\_RECORDS(tablename)###

Description
  Returns the total of records corresponding to a table name
  **considering a possible limit**.


.. _configuration-content-markers-record-offset:

RECORD\_OFFSET
~~~~~~~~~~~~~~

Name
  ###RECORD\_OFFSET###

Description
  Return the page offset. The page offset corresponds to the current
  position inside a global record set. This marker is useful when
  displaying a page browser. See marker ###PAGE\_BROWSER###. You can
  have something like this: ###RECORD\_OFFSET### / ###TOTAL\_RECORDS###
  which will display the current position among the total number of
  records.


.. _configuration-content-markers-start-at:

START\_AT
~~~~~~~~~

Name
  ###START\_AT###

Description
  Return the position of the first record returned by a subset,
  considering a possible limit.

  This marker is useful when displaying a page browser like this one :

  .. code-block:: text

		Records 1 – 10 of 2000 in total

  which would be coded like this in the template:

  .. code-block:: text

		Records ###START_AT### – ###STOP_AT### of ###TOTAL_RECORDS### in total


.. _configuration-content-markers-stop-at:

STOP\_AT
~~~~~~~~

Name
  ###STOP\_AT###

Description
  Return the position of the last record returned by a subset,
  considering a possible limit.

  See :ref:`###START_AT### <configuration-content-markers-start-at>` above.


.. _configuration-structure-markers:

Structure Markers
"""""""""""""""""


.. _configuration-structure-markers-loop:

LOOP
~~~~

Name
  <!--LOOP(loop\_name)-->

  <!--ENDLOOP-->

Description
  Where loop\_name is a table name.


.. _configuration-structure-markers-if:

IF
~~

Name
  <!--IF(###FIELD.maker### == 'value')-->

  <!--ELSE-->

  <!--ENDIF-->

Description
  Allows to display conditional content. Be careful to use parentheses
  around the condition. The :code:`ELSE` part is optional.

  .. warning::

     It is not possible to nest IF markers.


.. _configuration-structure-markers-empty:

EMPTY
~~~~~

Name
  <!--EMPTY-->

  <!--ENDEMPTY-->

Description
  This part is displayed only if the Data Structure is empty. Please
  mind that the rest of the template is still displayed.


.. _configuration-functions:

Functions
"""""""""


.. _configuration-functions-php-function:

PHP function
~~~~~~~~~~~~

Name
  FUNCTION:php\_function("###MARKER###",parameter1,...)

Description
  A PHP function. No simple / double quote required.

  **Examples:**

  .. code-block:: text

		FUNCTION:str_replace(P ,X, ###LABEL.title###)
		FUNCTION:str_repeat(###LABEL.title###,2)
		FUNCTION:md5(###LABEL.title###)


.. _configuration-functions-limit:

LIMIT
~~~~~

Name
  LIMIT(###MARKER###, 4)

Description
  Limit the number of words in a marker.

  **Examples:**

  :code:`LIMIT(###FIELD.description###, 4)` will return the first 4
  words of field description


.. _configuration-functions-count:

COUNT
~~~~~

Name
  COUNT(tableName)

Description
  Return the number of records from the Data Structure.

  .. tip::

     Add parameter :code:`debug[structure]` in the URL to see the Data Structure.
     (Works with a BE login).

  **Examples:**

  :code:`COUNT(tt\_content)` will return the number of records in table
  tt\_content


.. _configuration-functions-page-status:

PAGE\_STATUS
~~~~~~~~~~~~

Name
  PAGE\_STATUS(404)

  PAGE\_STATUS(404, page/404/)

  PAGE\_STATUS(404, pid:30)

  PAGE\_STATUS(301, new/page/)

Description
  If the Data Structure is empty, send the appropriate header and
  redirect link when needed.

  For the 404 status, leaving the redirect URL empty will make it fall
  back on the internal page not found handling of TYPO3.

  You can also specifiy a page uid to redirect to, using the "pid:"
  syntax. In this case, the query string is added to the link.


.. _configuration-functions-escaping-markers:

Escaping markers
""""""""""""""""

In the rare case where you content might contain a part like
:code:`###foobar###`, it will be stripped at the end of the rendering,
because templatedisplay cleans up all unreplaced markers. If you need
to display such content, you need to escape the hash-characters, like
:code:`\#\#\#foobar\#\#\#`. This will be replaced by :code:`###foobar###` at the
end of the processing, but after the clean up of unused markers.

