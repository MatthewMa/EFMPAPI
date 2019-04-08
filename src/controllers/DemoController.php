<?php
/**
 * Created by PhpStorm.
 * User: mma
 * Date: 2019-03-26
 * Time: 15:38
 */
namespace App\Controllers;
use Exception;
use PDOException;
use PDO;
use App\Controllers\smtp;

class DemoController extends Controller {
    /**
     * Get all demos
     * @param $request
     * @param $response
     * @return mixed
     */
    public function getDemos($request, $response) {
        try{
            $con = $this->getDb();
            $sql = "SELECT * FROM demos";
            $result = null;
            foreach ($con->query($sql) as $row) {
                $result[] = $row;
            }
            if($result){
                return $response->withJson(array('status' => 'true','result'=>$result),200);
            }else{
                return $response->withJson(array('status' => 'Users Not Found'),422);
            }

        }
        catch(Exception $ex){
            return $response->withJson(array('error' => $ex->getMessage()),422);
        }
    }

    public function addDemo($request, $response) {
        try{
            $con = $this->getDb();
            $sql = "INSERT INTO demos(username,telephone,company,email,comments) VALUES (:username,:telephone,:company,:email,:comments)";
            $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $values = array(
                ':username' => $request->getParam('username'),
                ':telephone' => $request->getParam('telephone'),
                ':company' => $request->getParam('company'),
                ':email' => $request->getParam('email'),
                ':comments' => $request->getParam('comments')
            );
            $pre->execute($values);
            $subject = "Demo Request";
            // message
            $message = "
                <html>
                  <head>
                      <title></title>
                  </head>
                  <body> 
                       <p>User: " . $request->getParam('username') . "</p>" .
                            "<p> Company: " . $request->getParam('company') ."</p>" .
                            "<p>Email: " . $request->getParam('email') . "</p>" .
                            "<p>Telephone: " . $request->getParam('telephone') ."</p>" .
                            "<p>Comments: " . $request->getParam('comments') . "</p>" .
                            "</body></html>";
            // Send email to Matthew and Michael
            require_once ('smtp.php');
            $smtpserver = "smtp.163.com";//SMTP server
            $smtpserverport =25;//SMTP port number
            $smtpusermail = "westernheritage@163.com";//user mail(from)
            $smtpemailto = "sma@westernheritage.ca";//send to whom
            $smtpuser = "westernheritage@163.com";//SMTP server account
            $smtppass = "botkaolith123";//third-party authentication code
            $mailsubject = $subject;//mail subject
            $mailbody = $message;//body
            $mailtype = "HTML";//format(html)
            $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//true-> authenticate the identity
            $smtp->debug = true;//whether to show the debug info
            $state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
            $smtpemailto = "mma@westernheritage.ca";//send to whom
            $state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
            $response->withHeader('Content-Type', 'application/json');
            if ($state=="") {
                // Send email fail
                return $response->withJson(array('error' => 'email failed'),422);
            } else {
                return $response->withJson(array('status' => 'User Created'),200);
            }
        }
        catch(Exception $ex){
            return $response->withJson(array('error' => $ex->getMessage()),422);
        }
    }
}