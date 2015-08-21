.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _installation:

Installation
------------

Extension "templatedisplay" is part of the Tesseract framework. It
will not do anything if installed alone.

After installation you must load the static TypoScript template for
proper rendering.

Templatedisplay can easily display a page browser, but this requires
extension "pagebrowse" to be installed too.


.. _installation-requirements:

Requirements
^^^^^^^^^^^^

Extension "templatedisplay" requires the PHP Simple XML library.

Versions 2.0.0 and above require TYPO3 6.2 or more.


Upgrading
^^^^^^^^^

Please read the sections below carefully to know if you are impacted
by changes in some versions.


Upgrading to 1.3.0
""""""""""""""""""

In version 1.3.0, the static TypoScript setup was changed to use a
reference to :code:`lib.parseFunc_RTE`, instead of making a copy. This was
made so that :code:`plugin.tx_templatedisplay.richtext.parseFunc` stays in
sync with :code:`lib.parseFunc_RTE`. The drawback is that you cannot make
changes like:

.. code-block:: typoscript

	plugin.tx_templatedisplay.richtext.parseFunc.foo = bar

anymore. If you did such changes before, you should first override the
reference by a copy and make your change again, e.g.

.. code-block:: typoscript

	plugin.tx_templatedisplay.richtext.parseFunc >
	plugin.tx_templatedisplay.richtext.parseFunc < lib.parseFunc_RTE
	plugin.tx_templatedisplay.richtext.parseFunc.foo = bar

