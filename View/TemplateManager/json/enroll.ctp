<?php
/**
 * COmanage Registry CO Template Manager API template
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Get a pointer to our model
$modelid = "co_id";

if(!empty($$modelid)) {
  print json_encode(array("ResponseType" => "NewObject",
                          "Version" => "1.0",
                          "ObjectType" => "Co",
                          "Id" => $$modelid)) . "\n";
} elseif(!empty($invalid_fields)) {
  print json_encode(array("ResponseType" => "ErrorResponse",
                          "Version" => "1.0",
                          "Id" => "New",
                          "InvalidFields" => $invalid_fields)) . "\n";
}
