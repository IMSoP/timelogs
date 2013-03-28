<?php

/**
 * Description of tui_smarty_security_policy
 *
 * @author ian.thomas
 */
class Tui_Smarty_Security_Policy extends Smarty_Security {
  // remove PHP tags
  public $php_handling = Smarty::PHP_REMOVE;

  // disable all PHP functions
  public $php_functions = null;
}
