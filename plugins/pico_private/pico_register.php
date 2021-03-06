<?php
/**
 * A plugin that allows users to be created for usage with Pico_Private.
 *
 * @author Timothy Su
 * @link http://timofeo.com/
 * @license http://opensource.org/licenses/MIT
 */

class Pico_Register
{

    private $theme;
    
    public function __construct()
    {
        $plugin_path = dirname(__FILE__);
        $this->path  = $plugin_path;
    }
    
    public function config_loaded(&$settings)
    {
        $this->theme = $settings['theme'];
    }
    
    public function request_url(&$url)
    {
        $this->url = $url;
        if ($url == 'register') {
            session_start();
            if ($_SESSION['authed'] == false) {
                session_write_close();
                return;
            } else {
                session_write_close();
                $this->redirect_home();
                exit;
            }
        }
    }
    
    public function before_render(&$twig_vars, &$twig)
    {
        if ((!isset($_SESSION['authed']) || $_SESSION['authed'] == false) && $this->url == 'register') {
            // shortHand $_POST variables
            $postUsername  = $_POST['username'];
            $postPassword  = $_POST['password'];
            $postPassword2 = $_POST['password2'];
            $postGrade     = $_POST['grade'];
            $postName      = $_POST['name'];
            if (isset($postUsername) && isset($postPassword) && isset($postPassword2) && isset($postGrade) && isset($postName)) {
                if (!preg_match('/\A[A-Za-z_\-0-9]+\z/', $postUsername)) {
                    $twig_vars['register_error'] = 'Make sure username only consists of alphanumeric characters and underscores.';
                    $twig_vars['username']    = $postUsername;
                    $twig_vars['grade']       = $postGrade;
                    $twig_vars['name']        = $postName;
                } else if (file_exists($this->path . '/users/' . $postUsername . '.xml')) {
                    $twig_vars['register_error'] = 'Username is already taken.  Please choose a different one.';
                    $twig_vars['username']    = $postUsername;
                    $twig_vars['grade']       = $postGrade;
                    $twig_vars['name']        = $postName;
                } else if ($postPassword != $postPassword2) {
                    $twig_vars['register_error'] = 'The passwords do not match.  Please re-enter.';
                    $twig_vars['username']    = $postUsername;
                    $twig_vars['grade']       = $postGrade;
                    $twig_vars['name']        = $postName;
                } else if ($postName == '') {
                    $twig_vars['register_error'] = 'Please enter a full name.';
                    $twig_vars['username']    = $postUsername;
                    $twig_vars['grade']       = $postGrade;
                    $twig_vars['name']        = $postName;
                } else {
                    $xml = new SimpleXMLElement('<user></user>');
                    $xml->addChild('password', md5($postPassword));
                    $xml->addChild('grade', $postGrade);
                    $xml->addChild('name', $postName);
                    $xml->asXML($this->path . '/users/' . $postUsername . '.xml');
                    session_start();
                    $_SESSION['authed']   = true;
                    if (isset($_SESSION['register_error'])) {
                        unset($_SESSION['register_error']);
                    }
                    if (isset($_SESSION['login_error'])) {
                        unset($_SESSION['login_error']);
                    }
                    $_SESSION['username'] = $postUsername;
                    session_write_close();
                    $this->redirect_home();
                    exit;
                }
                $_SESSION['register_error'] = $twig_vars['register_error'];
                session_write_close();
                $this->redirect_home();
                exit;
            } else {
                $twig_vars['register_error'] = 'Check inputs, please fill out every field.  Assure that the username consists of only alphanumeric characters and underscores.';
                $_SESSION['register_error'] = 'Check inputs, please fill out every field.  Assure that the username consists of only alphanumeric characters and underscores.';
                $twig_vars['username']    = $postUsername;
                session_write_close();
                $this->redirect_home();
                exit;
            }
            
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
            $loader                     = new Twig_Loader_Filesystem(THEMES_DIR . $this->theme);
            $twig_register              = new Twig_Environment($loader, $twig_vars);
            $twig_vars['meta']['title'] = "Register";
            echo $twig_register->render('register.html', $twig_vars);
            exit;
        }
        if (isset($_SESSION['register_error'])) {
            $twig_vars['register_error'] = $_SESSION['register_error'];
        }
    }
    
    private function redirect_home()
    {
        if (isset($_SESSION['register_error'])) {
            header('Location: /#displayregister');
        }
        else {
            header('Location: /');
        }
        exit;
    }
    
}