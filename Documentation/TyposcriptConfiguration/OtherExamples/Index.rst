.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _typoscript-configuration-examples:

Other examples
^^^^^^^^^^^^^^


.. _typoscript-configuration-examples-page-title:

Defining page title automatically
"""""""""""""""""""""""""""""""""

Example 1: defining the page title according to a field value, useful
for a detail view. **Make sure, "Display Controller (cached)" is defined.**
Otherwise, "substitutePageTitle" will have no effect.

.. code-block:: typoscript

	plugin.tx_templatedisplay {
		substitutePageTitle = {title} - {field_custom}
	}


.. _typoscript-configuration-examples-page-browser:

Setting the page browser parameters
"""""""""""""""""""""""""""""""""""

.. code-block:: typoscript

	plugin.tx_templatedisplay {
		pagebrowse {
			templateFile = fileadmin/templates/plugins/pagebrowse/template.html
			enableMorePages = 1
			enableLessPages = 1
			pagesBefore = 3
			pagesAfter = 3
		}
	}

