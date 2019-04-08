<?php
/**
 * Created by PhpStorm.
 * User: mma
 * Date: 2019-04-01
 * Time: 09:10
 */

namespace App\controllers;
use Exception;
use Firebase\JWT\JWT;
use PDOException;
use PDO;
use App\Controllers\smtp;



class UserController extends Controller {
    /**
     * Get all demos
     * @param $request
     * @param $response
     * @return mixed
     */
    public function getUsers($request, $response) {
        try{
            $con = $this->getDb();
            $sql = "SELECT * FROM users";
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

    /**
     * Get one user by userid
     * @param $request
     * @param $response
     * @return mixed
     */
    function getUserById($request,$response) {
        try{
            $id = $request->getAttribute('id');
            $con = $this->getDb();
            $sql = "SELECT * FROM users WHERE id = :id";
            $pre  = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $values = array(
                ':id' => $id);
            $pre->execute($values);
            $result = $pre->fetch();
            if($result){
                return $response->withJson(array('status' => 'true','result'=> $result),200);
            }else{
                return $response->withJson(array('status' => 'User Not Found'),422);
            }
        }
        catch(Exception $ex){
            return $response->withJson(array('error' => $ex->getMessage()),422);
        }

    }

    /**
     * User login function
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return mixed
     */
    public function login($request, $response, array $args) {

        $input = $request->getParsedBody();
        $sql = "SELECT * FROM users where UPPER(username)= UPPER(:username)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("username", $input['username']);
        $sth->execute();
        $user = $sth->fetchObject();
        $salt = getenv('SALT');
        $encrypted_password = md5(md5($input['password'] . $salt));
        // verify username address.
        if(!$user) {
            return $this->response->withJson(['error' => true, 'message' => 'User does not exist.']);
        }

        // verify password.
        if ($user->password != $encrypted_password) {
            return $this->response->withJson(['error' => true, 'message' => 'Password not matched.']);
        }

        if(!$user->email_confirmation) {
            return $this->response->withJson(['error' => true, 'message' => 'Email not confirm.']);
        }
        $token = self::getToken($user->id, $user->username);
        return $this->response->withJson(['token' => $token, 'id' => $user->id]);
    }

    public static function getToken($id, $user) {
        $secret = getenv("JWT_SECRET");
        // date: now
        $now = time();
        // date: now +1 hour
        $future = time()+(60*60);
        $token = array(
            'id' => $id, // User id
            'user' => $user, // username
            'iat' => $now, // Start time of the token
            'exp' => $future, // Time the token expires (+1 hour)
        );
        // Encode Jwt Authentication Token
        return JWT::encode($token, $secret, "HS256");
    }

}