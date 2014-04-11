<?php

class signup extends CI_Controller {

    private $profileFields = array(
        'id',
        'firstName',
        'maiden-name',
        'lastName',
        'picture-url',
        'email-address',
        'location:(country:(code))',
        'headline',
        'industry',
        'summary',
        'specialties',
        'interests',
        'public-profile-url',
        'last-modified-timestamp',
        'num-recommenders',
        'date-of-birth',
        'positions:(id,title,summary,start-date,end-date,is-current,company:(id,name,type,size,industry))',
        'educations',
        'courses',
        'languages:(id,language:(name),proficiency:(level))',
        'certifications:(id,name,number,authority:(name),start-date,end-date)',
        'skills',
        'phone-numbers',
        'main-address',
        'twitter-accounts',
        'recommendations-received',
    );

    function __construct() {
        parent:: __construct();
        $this->load->library('linkedin');
        session_name('linkedin'); // needed for likedin session
        session_start();
    }

    public function linkedin_signup() {

        if ($_GET['error'] == 'access_denied') {

            // handle the situation when the user clicks 'cancel' while auth
            header("location:" . base_url() . "linkedin_signup/denied");
            exit();
        } else if (isset($_GET['code'])) {

            // User authorised and has provided authorisation code

            if ($_SESSION['state'] == $_GET['state']) {
                // Get token so you can make API calls
                $this->linkedin->getAccessToken();
            } else {
                // CSRF attack? Or did you mix up your states?
                exit;
            }
        } else {

            // expired sessions, reset
            if ((empty($_SESSION['expires_at'])) || (time() > $_SESSION['expires_at'])) {
                // Token has expired, clear the state
                $_SESSION = array();
            }

            // don't yet have an an authorisation code
            if (empty($_SESSION['access_token'])) {
                // Start authorization process
                $this->linkedin->getAuthorizationCode();
            }
        }
        $profileData = $this->linkedin->fetch('GET', '/v1/people/~:(' . implode(',', $this->profileFields) . ')', $accessToken);

        try {
            if ($profileData->id == NULL) {// you will get nothing if client is premium on linkedin
                throw new Exception('Signup');
            }
        } catch (Exception $e) {
            // catch code
        }
    }

}
