#!/usr/bin/php
<?php
  /*
          File: curl_call.php
       Created: 07/22/2020
       Updated: 07/22/2020
    Programmer: Cuates
    Updated By: Cuates
       Purpose: API CURL call
  */

  // Include error check class
  include ("checkerrorclass.php");

  // Create an object of error check class
  $checkerrorcl = new checkerrorclass();

  // Set variables
  $developerNotify = 'cuates@email.com'; // Production email(s)
  // $developerNotify = 'cuates@email.com'; // Development email(s)
  $endUserEmailNotify = 'cuates@email.com'; // Production email(s)
  // $endUserEmailNotify = 'cuates@email.com'; // Development email(s)
  $externalEndUserEmailNotify = ''; // Production email(s)
  // $externalEndUserEmailNotify = 'cuates@email.com'; // Development email(s)
  $scriptName = 'Curl Call Ingestion'; // Production
  // $scriptName = 'TEST Curl Call Ingestion TEST'; // Development
  $fromEmailServer = 'Email Server';
  $fromEmailNotifier = 'email@email.com';

  // Retrieve any other issues not retrieved by the set_error_handler try/catch
  // Parameters are function name, $email_to, $email_subject, $from_mail, $from_name, $replyto, $email_cc and $email_bcc
  register_shutdown_function(array($checkerrorcl,'shutdown_notify'), $developerNotify, $scriptName . ' Error', $fromEmailNotifier, $fromEmailServer, $fromEmailNotifier);

  // Function to catch exception errors
  set_error_handler(function ($errno, $errstr, $errfile, $errline)
  {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
  });

  // Attempt to generate email
  try
  {
    // Declare download directory
    define ('DOWNLOADDIR', '/var/www/html/Doc_Directory/');
    define ('TEMPDOC', '/var/www/html/Temp_Directory/');

    // Include database class file
    include ("curl_class.php");

    // Create an object of database class
    $curl_cl = new curl_class();

    // Initialize variables
    $jobName = "Job Name";
    $dataValue = array();
    $errorPrefixFilename = "curl_call_issue_"; // Production
    // $errorPrefixFilename = "curl_call_dev_issue_"; // Development
    $errormessagearray = array();
    $existResp = array('Issue One', 'Issue Two', 'Issue Three');
    $filename = "";
    $filenamePrefix = 'File_Name_';
    $headerDataInformation = array();
    $headerValue = array();
    $finalHeaderDataValues = array();
    $dataValueSuccess = array();
    $lineBreakString = array("\r\n", "\r", "\n");
    $idNum = 0;

    // Call function to insert and or update any new data into the database table
    $regData = $curl_cl->registerData($jobName);

    // Explode database message
    $regDataReturn = explode('~', $regData);

    // Set response message
    $regDataResp = reset($regDataReturn);
    $regDataMesg = next($regDataReturn);

    // Check if error with registering process
    if (trim($regDataResp) !== "Success")
    {
      // Append error message
      array_push($errormessagearray, array('Register', $jobName, '', '', '', '', '', '', 'Error', $regDataMesg));
    }

    // Function call to retrieve data values
    $dataValue = $curl_cl->getData($jobName);

    // Check if server error
    if (!isset($dataValue['SError']) && !array_key_exists('SError', $dataValue))
    {
      // Loop through all values in the array for values in each record
      foreach($dataValue as $valueRec)
      {
        // Initialize parameters
        $column01 = reset($valueRec);
        $column02 = next($valueRec);
        $column03 = next($valueRec);

        // Call authentication API
        $authAPI = $curl_cl->getAuthAPI();

        // Split string
        $authAPIRespArray = explode('~', $authAPI);

        // Set response and message
        $authAPIResp = reset($authAPIRespArray);
        $authAPIMesg = next($authAPIRespArray);

        // Check if the authentication was successful
        if ($authAPIResp === "Success")
        {
          // Change the return string to a json object array
          $authResponse = json_decode($authAPIMesg, true);

          // Check if JSON was decoded properly
          if (json_last_error() == JSON_ERROR_NONE)
          {
            // Check if authentication has been returned
            if(isset($authResponse["access_token"]))
            {
              // Set access token
              $accessToken = "";
              $accessToken = $authResponse["access_token"];

              // Set up JSON string
              $jsonString = "";
              $jsonString = '{
                "column01": "' . $column01 . '",
                "column02": "' . $column02 . '",
                "column03": "' . $column03 . '",
                "column04": ""
              }';

              // Attempt to post the JSON string against the API
              $postAPIResponse = $curl_cl->postDataAPI($jsonString, $accessToken);

              // Split string
              $postAPIRespArray = explode('~', $postAPIResponse);

              // Set response and message
              $postAPIResp = reset($postAPIRespArray);
              $postAPIMesg = next($postAPIRespArray);

              // Check if the authentication was successful
              if ($postAPIResp === "Success")
              {
                // Change the return string to a json object array
                $postResponse = json_decode($postAPIMesg, true);

                // Check if JSON was decoded properly
                if (json_last_error() == JSON_ERROR_NONE)
                {
                  // Check if status code is set
                  if(isset($postResponse["id"]))
                  {
                    // Push record into array for later processing
                    array_push($dataValueSuccess, array($column01, $column02, $column03));

                    // Retrieve id
                    $idretnumber = $curl_cl->extractIDData($column03);

                    // Check if server error
                    if (!isset($idretnumber['SError']) && !array_key_exists('SError', $idretnumber))
                    {
                      // Initialize parameters
                      $idvalue = reset($idretnumber);

                      // Validate data
                      $validateDataResult = $curl_cl->validateData($idvalue, $column03);

                      // Explode database message
                      $validateDataReturn = explode('~', $validateDataResult);

                      // Set response message
                      $validateDataResp = reset($validateDataReturn);
                      $validateDataMesg = next($validateDataReturn);

                      // Check if error with registering process
                      if (trim($validateDataResp) !== "Success")
                      {
                        // Append error message
                        array_push($errormessagearray, array('Validate Data', $jobName, $column01, $column02, $column03, '', $idvalue, '', 'Error', $validateDataMesg));

                      }
                    }
                    else
                    {
                      // Set message
                      $idRetNumberMesg = reset($idretnumber);

                      // Append error message
                      array_push($errormessagearray, array('Retrieve ID Data', $jobName, $column01, $column02, $column03, '', '', '', 'Error', $idRetNumberMesg));

                    }
                  }
                  else
                  {
                    // Append error message
                    array_push($errormessagearray, array('External API', $jobName, $column01, $column02, $column03, '', '', '', 'Error', str_replace('"', '', str_replace($lineBreakString, '', $postAPIMesg))));

                    // Check if the id serial message already exist within the the array
                    if (in_array(strtolower(str_replace('"', '', str_replace($lineBreakString, '', $postAPIMesg))), $existResp))
                    {
                      // Retrieve id
                      $idretnumber = $curl_cl->extractIDData($column03);

                      // Check if server error
                      if (!isset($idretnumber['SError']) && !array_key_exists('SError', $idretnumber))
                      {
                        // Initialize parameters
                        $idvalue = reset($idretnumber);

                        // Validate data
                        $validateDataResult = $curl_cl->validateData($idvalue, $column03);

                        // Explode database message
                        $validateDataReturn = explode('~', $validateDataResult);

                        // Set response message
                        $validateDataResp = reset($validateDataReturn);
                        $validateDataMesg = next($validateDataReturn);

                        // Check if error with validating
                        if (trim($validateDataResp) !== "Success")
                        {
                          // Append error message
                          array_push($errormessagearray, array('Validate Data Exist Already', $jobName, $column01, $column02, $column03, '', $idvalue, '', 'Error', $validateDataMesg));

                        }
                      }
                      else
                      {
                        // Set message
                        $idRetNumberMesg = reset($idretnumber);

                        // Append error message
                        array_push($errormessagearray, array('Retrieve ID Data Exist Already', $jobName, $column01, $column02, $column03, '', '', '', 'Error', $idRetNumberMesg));
                      }
                    }
                  }
                }
                else
                {
                  // Append error message
                  array_push($errormessagearray, array('External API JSON Issue', $jobName, $column01, $column02, $column03, '', '', '', 'Error', str_replace($lineBreakString, '', $postAPIMesg)));
                }
              }
              else
              {
                // Append error message
                array_push($errormessagearray, array('External API Issue', $jobName, $column01, $column02, $column03, '', '', '', 'Error', str_replace($lineBreakString, '', $postAPIMesg)));
              }
            }
            else
            {
              // Append error message
              array_push($errormessagearray, array('External Auth API', $jobName, $column01, $column02, $column03, '', '', '', 'Error', str_replace($lineBreakString, '', $authAPIMesg)));

            }
          }
          else
          {
            // Append error message
            array_push($errormessagearray, array('External Auth JSON Issue', $jobName, $column01, $column02, $column03, '', '', '', 'Error', str_replace($lineBreakString, '', $authAPIMesg)));
          }
        }
        else
        {
          // Append error message
          array_push($errormessagearray, array('External Auth API Issue', $jobName, $column01, $column02, $column03, '', '', '', 'Error', str_replace($lineBreakString, '', $authAPIMesg)));
        }
      }

      // Check if count of data retrieved is greater than zero
      if (count($dataValueSuccess) > 0)
      {
        // Copy array for later modifications
        $finalHeaderDataValues = $dataValueSuccess;

        // Initialize column headers
        $colHeaders = array();

        // Build the filename
        $filename = $filenamePrefix . date("YmdyHis") . ".txt";

        // Build initial row information
        $headerDataInformation = array('START', count($dataValueSuccess), $filename);

        // Define attribute names
        $headerValue = array('Column01', 'Column02', 'Column03');

        // Build the array with the information needed for data feed transfer
        array_unshift($finalHeaderDataValues, $headerValue);
        array_unshift($finalHeaderDataValues, $headerDataInformation);

        // Write to file for later processing
        $createFileNameResult = $curl_cl->writeToFile(DOWNLOADDIR, $filename, $finalHeaderDataValues, $colHeaders);

        // Explode return function message
        $createFileNameReturn = explode('~', $createFileNameResult);

        // Set response message
        $createFileNameResp = reset($createFileNameReturn);
        $createFileNameMesg = next($createFileNameReturn);

        // Check if error with creating file
        if (trim($createFileNameResp) === "Success")
        {
          // Send an email to end user for notification purpose only
          $toEndUser = "";
          $toEndUser = $externalEndUserEmailNotify;
          $to_ccEndUser = "";
          $to_bccEndUser = "";
          $to_bccEndUser = $endUserEmailNotify;

          $fromEmailEndUser = $fromEmailNotifier;
          $fromNameEndUser = $fromEmailServer;
          $replyToEndUser = $fromEmailNotifier;

          // Set the subject line
          $subjectEndUser = $scriptName . " has been Processed";

          // Set the email headers
          $headersEndUser = "From: " . $fromEmailServer . " <" . $fromEmailNotifier . ">" . "\r\n";
          // $headersEndUser .= "CC: " . $to_cc . "\r\n";
          // $headersEndUser .= "BCC: " . $to_bcc . "\r\n";
          $headersEndUser .= "MIME-Version: 1.0\r\n";
          $headersEndUser .= "Content-Type: text/html; charset=UTF-8\r\n";
          // $headersEndUser .= "X-Priority: 3\r\n";

          // Mail priority levels
          // "X-Priority" (values: 1, 3, or 5 from highest[1], normal[3], lowest[5])
          // Set priority and importance levels
          $xPriorityEndUser = "";

          // Set the email body message
          $messageEndUser = "<!DOCtype html>
          <html>
            <head>
              <title>"
              . $scriptName .
                " has been Processed
              </title>
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
              <!-- Include next line to use the latest version of IE -->
              <meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\" />
            </head>
            <body>
              <div style=\"text-align: center;\">
                <h2>"
                  . $scriptName .
                  " has been Processed
                </h2>
              </div>";

          // Begin error message
          $messageEndUser .= "<div style=\"text-align: center;\"> " . $scriptName . " " . $filename . " file has been processed.
                          <br />
                          <br />
                          Do not reply, your intended recipient will not receive the message.
                      </div>";

          // Append the ending message
          $messageEndUser .= "</body>
          </html>";

          // Send email to user for successful POST
          $curl_cl->notifyEndUser($filename, DOWNLOADDIR, $toEndUser, $fromEmailEndUser, $fromNameEndUser, $replyToEndUser, $subjectEndUser, $headersEndUser, $messageEndUser, $to_ccEndUser, $to_bccEndUser, $xPriorityEndUser);
        }
        else
        {
          // Append error message
          array_push($errormessagearray, array('Write Data to File', $jobName, '', '', '', $filename, '', '', 'Error', $createFileNameMesg));
        }
      }
    }
    else
    {
      // Set message
      $dataValueMesg = reset($dataValue);

      // Append error message
      array_push($errormessagearray, array('Data Extract', $jobName, '', '', '', '', '', '', 'Error', $dataValueMesg));
    }

    // Update the sequence in the database
    $sequenceUpdate = $curl_cl->updateSequence($idNum);

    // Explode database message
    $sequenceUpdateData = explode('~', $sequenceUpdate);

    // Set response message
    $sequenceUpdateResp = reset($sequenceUpdateData);
    $sequenceUpdateMesg = next($sequenceUpdateData);

    // Check if error with registering process
    if (trim($sequenceUpdateResp) !== "Success")
    {
      // Append error message
      array_push($errormessagearray, array('Update Sequence', $jobName, '', '', '', '', '', $idNum, 'Error', $sequenceUpdateMesg));
    }

    // Check if error message array is not empty
    if (count($errormessagearray) > 0)
    {
      // Set prefix file name and headers
      $errorFilename = $errorPrefixFilename . date("Y-m-d_H-i-s") . '.csv';
      $colHeaderArray = array(array('Process', 'Job Name', 'Column 01', 'Column 02', 'Column 03', 'File Name', 'ID', 'Sequence Number', 'Response', 'Message'));

      // Initialize variable
      $to = "";
      $to = $developerNotify;
      $to_cc = "";
      $to_bcc = "";
      $fromEmail = $fromEmailNotifier;
      $fromName = $fromEmailServer;
      $replyTo = $fromEmailNotifier;
      $subject = $scriptName . " Error";

      // Set the email headers
      $headers = "From: " . $fromEmailServer . " <" . $fromEmailNotifier . ">" . "\r\n";
      // $headers .= "CC: " . $to_cc . "\r\n";
      // $headers .= "BCC: " . $to_bcc . "\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
      // $headers .= "X-Priority: 3\r\n";

      // Mail priority levels
      // "X-Priority" (values: 1, 3, or 5 from highest[1], normal[3], lowest[5])
      // Set priority and importance levels
      $xPriority = "";

      // Set the email body message
      $message = "<!DOCtype html>
      <html>
        <head>
          <title>
            Cron Job " . $scriptName . " Error
          </title>
          <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
          <!-- Include next line to use the latest version of IE -->
          <meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\" />
        </head>
        <body>
          <div style=\"text-align: center;\">
            <h2>"
              . $scriptName .
              " Error
            </h2>
          </div>
          <div style=\"text-align: center;\">
            There was an issue with " . $scriptName . " Error process.
            <br />
            <br />
            Do not reply, your intended recipient will not receive the message.
          </div>
        </body>
      </html>";

      // Call notify developer function
      $multi_curl_call_cl->notifyDeveloper(TEMPDOC, $errorFilename, $colHeaderArray, $errormessagearray, $to, $to_cc, $to_bcc, $fromEmail, $fromName, $replyTo, $subject, $headers, $message, $xPriority);
    }
  }
  catch(Exception $e)
  {
    // Call to the function
    $checkerrorcl->caught_error_notify($e, $developerNotify, $scriptName . ' Error', $fromEmailNotifier, $fromEmailServer, $fromEmailNotifier);
  }
?>