<?php
/**
 * A plugin that allows for site-wide authentication via. a flat-file database
 *
 * @author Timothy Su
 * @link http://timofeo.com/
 * @license http://opensource.org/licenses/MIT
 */

class Pico_Private
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
        if ($url == 'login') {
            if ($_SESSION['authed'] == false) {
                return;
            } else {
                $this->redirect_home();
                exit;
            }
        }
        if ($url == 'logout') {
            session_destroy();
            $this->redirect_home();
        }
    }
    
    public function before_render(&$twig_vars, &$twig)
    {
        if ((!isset($_SESSION['authed']) || $_SESSION['authed'] == false) && $this->url == 'login') {
            // shortHand $_POST variables
            $postUsername = $_POST['username'];
            $postPassword = $_POST['password'];
            if (isset($postUsername) && isset($postPassword)) {
                if (file_exists($this->path . '/users/' . $postUsername . '.xml')) {
                    $xml = simplexml_load_file($this->path . '/users/' . $postUsername . '.xml');
                }
                if ((file_exists($this->path . '/users/' . $postUsername . '.xml') == true) && ($xml->password == md5($postPassword))) {
                    $_SESSION['authed']   = true;
                    $_SESSION['username'] = $postUsername;
                    $this->redirect_home();
                } else {
                    $twig_vars['login_error'] = 'Invalid login';
                    $twig_vars['username']    = $postUsername;
                }
            }
            
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
            $loader                     = new Twig_Loader_Filesystem(THEMES_DIR . $this->theme);
            $twig_login                 = new Twig_Environment($loader, $twig_vars);
            $twig_vars['meta']['title'] = "Login";
            echo $twig_login->render('login.html', $twig_vars);
            exit;
        }
        
        if (isset($_SESSION['authed']) && isset($_SESSION['username'])) {
            $twig_vars['authed']   = $_SESSION['authed'];
            $twig_vars['username'] = $_SESSION['username'];
        } else {
            $twig_vars['authed']   = false;
            $twig_vars['username'] = '';
        }
    }
    
    private function redirect_home()
    {
        header('Location: /');
        exit;
    }
    
}