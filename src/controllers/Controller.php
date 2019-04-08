<?php
/**
 * Created by PhpStorm.
 * User: mma
 * Date: 2019-03-26
 * Time: 16:29
 */

namespace App\Controllers;
use PDOException;
use Slim\Views\Twig;
use PDO;

class Controller
{
    protected $container;
    public function __construct($c) {
        $this->container = $c;
    }

    public function __get($property) {
        if($this->container->has($property)) {
            return $this->container->get($property);
        }
        return $this->{$property};
    }
    public function getDb() {
        if ($this->container['db'] instanceof PDO) {
            return $this->container['db'];
        }
    }
}