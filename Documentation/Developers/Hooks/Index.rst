.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _developers-hooks:

Hooks
^^^^^

Hooks offer an opportunity to step into the process at various points.
They offer the possibility to manipulate data and influence the final
output. Hooks can be used to replace personalized markers, introduced
previously in the HTML template. There is a convention in
templatedisplay to name Hook like :code:`###HOOK.myHook###`.

In templatedisplay, there are 2 available hooks:

- preProcessResult (for pre-processing the HTML template)

- postProcessResult (for post-processing the HTML content)

To facilitate the implementation of a hook, a skeleton file can be
found in
:file:`EXT:templatedisplay/samples/class.tx\_templatedisplay\_hook.php`.


.. _developers-hooks-step-1:

Step 1
""""""

In the :file:`ext_localconf.php` of your extension, register the hook:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templatedisplay']['postProcessResult']['myHook'][] = 'EXT:templatedisplay/class.tx_templatedisplay_hook.php:&tx_templatedisplay_hook';

Remarks:

- "postProcessResult" can be replaced by "preProcessResult".

- "myHook" can be something else but must correspond to the marker
  :code:`###HOOK.myHook###`.

- Make sure the path of the file is correct and suits your environment.

- Don't forget to clear the configuration cache!


.. _developers-hooks-step-2:

Step 2
""""""

Write the PHP method that will transform the content.

.. code-block:: php

	class tx_templatedisplay_hook {
		public function postProcessResult($content, $marker, &$pObj) {
			$controller = $pObj->getController();
			$data = $controller->cObj->data;
			if ($data['uid'] == 11399) {
				$_content = '';
				$content = str_replace('###HOOK.myHook###', $_content, $content);
			}
			return $content;
		}
	}

