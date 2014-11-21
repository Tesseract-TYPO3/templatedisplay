.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _typoscript-configuration-default-rendering:

Default rendering
^^^^^^^^^^^^^^^^^

A default rendering can be defined for each element type. The static
template provided with the extension contains the following:

.. code-block:: typoscript

	plugin.tx_templatedisplay {
		defaultRendering {
			richtext.parseFunc < lib.parseFunc_RTE
		}
	}

This configuration copies the RTE parseFunc into the parseFunc for the
rich text-type element, making possible to render correctly RTE-
enabled fields. Here's an example configuration:

.. code-block:: typoscript

	plugin.tx_templatedisplay {
		defaultRendering {
			text.wrap = <span class=”text”>|</span>
		}
	}

This would wrap a span tag with a “text” class around **every**
text-type element rendered by Template Display.

