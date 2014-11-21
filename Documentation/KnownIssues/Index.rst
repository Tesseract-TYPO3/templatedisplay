.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _known-issues:

Known issues
------------


.. _known-issues-nested-if:

Nested IF markers don't work
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: text

	<--IF()-->
		...
		<--IF()-->
			...
		<!--ENDIF-->
		...
	<!--ENDIF-->

This feature seems to be obvious, but is quite difficult to implement,
though (at least, with the actual code base). It comes from the way
the template engine works and particularly the general use of regular
expressions to handle the HTML. It would require a complex analysis of
the template to cut up in right parts and supbarts the "IF" markers.

Experience has shown that it's possible to live without this feature.
If the need of nested "IF" markers is required, you many want to have
a look at the "phpdisplay" or "fluiddisplay" Data Consumers.

Reference: http://forge.typo3.org/issues/show/1954


.. _known-issues-nested-multiple-edit:

Multiple edition is not possible (module web > list)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This would require too much effort for very small benefit. The cases
of multiple edition in templatedisplay are very rare.

Reference: http://forge.typo3.org/issues/show/1982

