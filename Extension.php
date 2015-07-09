<?php

namespace Bolt\Extension\AndyJessop\ContactForm;

use Bolt\Application;
use Bolt\BaseExtension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Extension extends BaseExtension
{
    const EMAIL = "andy@andyjessop.com";
    const SUBJECT = "New contact form submission";

    public function initialize() {

    	$this->app->post('api/forms/contact', array($this, 'handleSubmission'))
            ->bind('handleSubmission');
    }

    public function getName()
    {
        return "Contact Form";
    }

    /**
     * Handles contact form submission
     * @param  Request $request  POST request from form
     * @return json  
     */
    public function handleSubmission(Request $request)
    {
        $data = $this->retrieveFormData($request);

        $validation = $this->validateFields($data);

        if (count($validation) > 0)
        {
        	$response = $this->app->json(array(
        		'errors' => $validation
        	), 400);
        	return $response;
        }

       	// Send email and return json response
        return $this->sendEmail($data);
    }

    /**
     * Retrieves data from form
     * @param  Request      $request
     * @return stdClass     form data    
     */
    private function retrieveFormData($request)
    {
        $data = new \stdClass();

        $data->name = $request->get('name');
        $data->email = $request->get('email');
        $data->message = $request->get('message');

        return $data;
    }

    /**
     * Validates the input
     * @param  string $name    name on contact form
     * @param  string $email   email address
     * @param  string $message contact message
     * @return array           array of errors
     */
    private function validateFields($data)
    {
    	$errors = [];

    	if (!preg_match("/[-0-9a-zA-Z ]{2,60}/", $data->name))
    	{
    		$error = 'The name field is required';
    		array_push($errors, $error);
    	}

    	if (!preg_match("/[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]/", $data->email))
    	{
    		$error = 'The email field is invalid';
    		array_push($errors, $error);
    	}

    	if (!preg_match("/[-0-9a-zA-Z .]{2,2000}/", $data->message))
    	{
    		$error = 'The message field is not valid. Must be under 2000 characters.';
    		array_push($errors, $error);
    	}

    	return $errors;

    }

    /**
     * Performs the mail sending
     * @param  obj $data      validated data
     * @return Response       json response
     */
    private function sendEmail($data)
    {
        $body = 
            "From: " . $data->name . "\n" .
            "Email: " . $data->email . "\n" .
            "Message: " . $data->message . "\n";

        try {
            $message = $this->app['mailer']
                ->createMessage('message')
                ->setSubject(Extension::SUBJECT)
                ->setFrom($data->email)
                ->setTo(Extension::EMAIL)
                ->setBody(strip_tags($data->message));

            $this->app['mailer']->send($message);

            $response = $this->app->json(array(
                'message' => 'Message Sent!'
            ), 200);

        } catch (\Exception $e) {

            $error = "The 'mailoptions' need to be set in app/config/config.yml";

            $app['logger.system']->error($error, array('event' => 'config'));

            $response = $this->app->json(array(
                'message' => $error
            ), 500);
        }

        return $response;
    }

}






