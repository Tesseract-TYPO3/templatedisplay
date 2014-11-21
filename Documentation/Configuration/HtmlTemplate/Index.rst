.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _configuration-html-template:

HTML Template setup
^^^^^^^^^^^^^^^^^^^

The HTML Template can be defined of two manners.

- insert inline HTM directly in the text box

- external file loaded with following syntax:
  :code:`FILE:fileadmin/templates/plugins/tesseract/news/list_of_news.html`.

- as of TYPO3 CMS 6.0, point to a file referenced by the File
  Abstraction Layer, using the syntax: :code:`FILE:123` ,
  where "123" is the id of the "sys\_file" entry. Usage will be properly
  referenced.

External files would have the benefit to make use of an external
editor which is more convenient when editing large templates.
