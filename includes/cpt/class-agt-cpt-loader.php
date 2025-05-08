<?php

class AGT_CPT_Loader
{
  public static function init()
  {
    AGT_CPT_Resource::get_instance();
    AGT_CPT_FAQ::get_instance();
  }
}
