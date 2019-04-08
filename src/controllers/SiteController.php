<?php
/**
 * Created by PhpStorm.
 * User: mma
 * Date: 2019-04-02
 * Time: 15:59
 */

namespace App\controllers;
use Exception;
use Firebase\JWT\JWT;
use PDOException;
use PDO;

class SiteController extends Controller
{
    /**
     * Get all sites
     * @param $request
     * @param $response
     * @return mixed
     */
    public function getSites($request, $response) {
        try{
            $con = $this->getDb();
            $sql = "SELECT * FROM sites";
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
     * Get one site by siteid
     * @param $request
     * @param $response
     * @return mixed
     */
    function getSiteById($request,$response) {
        try{
            $id = $request->getAttribute('id');
            $con = $this->getDb();
            $sql = "SELECT * FROM sites WHERE id = :id";
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
}