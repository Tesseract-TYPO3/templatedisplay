.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _developers-custom-types:

Custom element types
^^^^^^^^^^^^^^^^^^^^

It is possible to define custom element types. Such types will be
added to the list of available types in the mapping interface, which
makes them easier to use for users than the user-function type.

As for hooks this is a two-step process.


.. _developers-custom-types-step-1:

Step 1
""""""

Register the custom type in file:`ext_localconf.php` file of your extension.
The syntax is as follows:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['types']['tx_test_mytype'] = array(
		'label' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:mytype',
		'icon'  => 'EXT:' . $_EXTKEY . '/mytype.png',
		'class' => 'tx_test_templatedisplay'
	);

The custom type is registered with a specific key (e.g.
“tx\_test\_mytype”) and with the following information:

- a label that will appear in the drop-down list of available element
  types (as well as alt text for the icon)

- an icon that will appear in the mapping interface when that type has
  been selected

- a class to do the processing of that custom type. The class must
  implement the :code:`tx_templatedisplay_CustomType` interface (more below).

Don't forget to register the class with the autoloader.


.. _developers-custom-types-step-2:

Step 2
""""""

The method itself is expected to do the rendering. It receives the
following parameters:

$value
  Type
     mixed
  Description
    The current value of the field that was mapped.

$conf
  Type
    array
  Description
     TypoScript configuration for the rendering (this may be ignore if you
     don't need TypoScript).

$pObj
  Type
    object
  Description
     A reference to the calling :code:`tx_templatedisplay` object.


A sample implementation is provided in the
:file:`samples/class.tx_templatedisplay_phonetype.php` file. The code
looks like this (without comments):

.. code-block:: php

	class tx_templatedisplay_PhoneType implements tx_templatedisplay_CustomType {
		function render($value, $conf, tx_templatedisplay $pObj) {
			$rendering = '<a href="callto://' . rawurlencode($value) . '">' . $value . '</a>';
			return $rendering;
		}
	}

In this simple example the class just does some minor processing with
the value it receives and returns the result.

Such classes should implement the :code:`t3lib_Singleton` interface so that only one instance of it is
created (otherwise one instance is created for each field using this
custom type on each pass in the loop). This will save memory.

