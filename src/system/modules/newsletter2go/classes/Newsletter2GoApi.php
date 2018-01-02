<?php

namespace Contao;

class Newsletter2GoApi extends \Controller
{
    /** @var  Newsletter2GoApi */
    private static $instance;

    /** @var int  */
    private $version = 4002;

    /** @var Newsletter2GoModel  */
    private $n2goModel;

    /**
     * __construct function.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->n2goModel = Newsletter2GoModel::getInstance();
    }

    /**
     * Gets an instance from itself.
     *
     * @return Newsletter2GoApi
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Newsletter2GoApi();
        }

        return self::$instance;
    }

    public function run()
    {
        $action = \Input::post('action');
        $apiKey = \Input::post('apiKey');

        try {
            if (!$apiKey) {
                $response = Nl2go_ResponseHelper::generateErrorResponse('API Key empty or not found in request!', Nl2go_ResponseHelper::ERRNO_PLUGIN_CREDENTIALS_MISSING);
            } else if (!$this->checkApiKey($apiKey)) {
                $response = Nl2go_ResponseHelper::generateErrorResponse('API Key is invalid!', Nl2go_ResponseHelper::ERRNO_PLUGIN_CREDENTIALS_WRONG);
            } else {
                switch ($action) {
                    case 'pluginVersion':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse(array('version' => $this->version));
                        break;
                    case 'getCustomerFields':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse(array('fields' => $this->getCustomerFields()));
                        break;
                    case 'getCustomerGroups':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse($this->getCustomerGroups());
                        break;
                    case 'getCustomers':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse($this->getCustomers());
                        break;
                    case 'getCustomersCount':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse($this->getCustomersCount());
                        break;
                    case 'getPost':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse($this->getPostData());
                        break;
                    case 'changeSubscriberStatus':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse($this->changeSubscriberStatus());
                        break;
                    case 'test':
                        $response = Nl2go_ResponseHelper::generateSuccessResponse();
                        break;
                    default:
                        $response = Nl2go_ResponseHelper::generateErrorResponse('Invalid or unknown action method call', Nl2go_ResponseHelper::ERRNO_PLUGIN_OTHER);
                        break;
                }
            }
        } catch (\Exception $e) {
            $response = Nl2go_ResponseHelper::generateErrorResponse($e->getMessage(), Nl2go_ResponseHelper::ERRNO_PLUGIN_OTHER);
        }

        header('Content-Type: application/json');
        echo $response;
        exit;
    }

    /**
     * Exports article information in required format
     * @return array
     */
    private function getPostData()
    {
        $result = array();
        $postId = \Input::post('postId');

        $arrNews = $this->n2goModel->findNewsByIdOrAlias($postId); //  check if there's news with id or alias

        if ($arrNews) {

            $objNews = \NewsModel::findByIdOrAlias($arrNews['id'], array( $arrNews['pid'] ));

            if (!empty($arrNews['jumpTo'])) {
                /** @var \PageModel $objPage */
                $objPage = \PageModel::findById($arrNews['jumpTo']);
            } else {
                /** @var \NewsArchiveModel $objNewsArchive */
                $objNewsArchive = \NewsArchiveModel::findById($arrNews['pid']);

                /** @var \PageModel $objPage */
                $objPage = \PageModel::findById($objNewsArchive->jumpTo);
            }

            /** @var \ContentModel $contentElement */
            $contentElement = \ContentModel::findPublishedByPidAndTable($arrNews['id'] , 'tl_news');

            $strNews = '';
            if($contentElement) {
                $arrayModels = $contentElement->getModels();
                foreach ($arrayModels as $contentModel) {
                    $strNewsTemp = $this->replaceInsertTags(self::getContentElement($contentModel), false);
                    $strNewsTemp = html_entity_decode($strNewsTemp, ENT_QUOTES, \Config::get('characterSet'));
                    $strNewsTemp = $this->convertRelativeUrls($strNewsTemp);
                    $strNews = $strNews . $strNewsTemp;
                }
            }

            $result['id'] = $postId;
            $result['title'] = $objNews->headline;
            $objResult = $objNews;
            $strResult = $strNews;

        } else {

            $objArticle = \ArticleModel::findByIdOrAliasAndPid($postId, null);

            if (!$objArticle) {
                return array(
                    'success' => false,
                    'message' => "Article or News with id ($postId) not found!",
                    'errorcode' => Nl2go_ResponseHelper::ERRNO_PLUGIN_OTHER,
                );
            }

            /** @var \PageModel $objPage */
            $objPage = \PageModel::findById($objArticle->pid);

            $objArticle->printable = 0;
            
            $strArticle = $this->replaceInsertTags(self::getArticle($objArticle), false);
            $strArticle = html_entity_decode($strArticle, ENT_QUOTES, \Config::get('characterSet'));
            $strArticle = $this->convertRelativeUrls($strArticle);

            $result['id'] = $postId;
            $result['title'] = $objArticle->title;

            $objResult = $objArticle;
            $strResult = $strArticle;
        }

        $result['url'] = \Environment::get('base');
        $result['link'] = $objPage->getFrontendUrl();
        $result['description'] = preg_replace('/<!--(.|\s)*?-->/', '', $strResult);
        $result['shortDescription'] = $objResult->teaser;
        $result['category'] = array();
        if (isset($objResult->keywords)) {
            $result['tags'] = explode(',', $objResult->keywords);
        } else {
            $result['tags'] = array();
        }
        $result['date'] = date("Y-m-d H:i:s", $objResult->tstamp);
        $result['images'] = array();

        // extract images from source
        $imagesArray = array();
        preg_match_all('/<img[^>]+>/i', $result['description'], $imagesArray);
        foreach ($imagesArray[0] as $image) {
            $tempImage = array();
            preg_match_all('/(src)="([^"]*)"/i', $image, $tempImage);
            $result['images'][] = $tempImage[2][0];
        }

        $objAuthor = UserModel::findById($objResult->author);
        $result['author'] = $objAuthor->username;
        
        return array('post' => $result);
    }

    /**
     * @param string $apiKey
     * @return bool
     */
    private function checkApiKey($apiKey)
    {
        return $this->n2goModel->getConfigValue('apiKey') == $apiKey;
    }

    /**
     * Exports all member fields
     * @return array
     */
    private function getCustomerFields()
    {
        $fields = array();
        $fields['id'] = $this->createField('id', 'Member Id', '', 'Integer');
        $fields['firstname'] = $this->createField('firstname', 'First name');
        $fields['lastname'] = $this->createField('lastname', 'Last name');
        $fields['dateOfBirth'] = $this->createField('dateOfBirth', 'Date of birth', '', 'Date');
        $fields['gender'] = $this->createField('gender', 'Gender');
        $fields['company'] = $this->createField('company', 'Company');
        $fields['street'] = $this->createField('street', 'Street');
        $fields['postal'] = $this->createField('postal', 'Postal Code');
        $fields['city'] = $this->createField('city', 'City');
        $fields['state'] = $this->createField('state', 'State');
        $fields['country'] = $this->createField('country', 'Country');
        $fields['phone'] = $this->createField('phone', 'Phone');
        $fields['mobile'] = $this->createField('mobile', 'Mobile');
        $fields['fax'] = $this->createField('fax', 'Fax number');
        $fields['email'] = $this->createField('email', 'Email');
        $fields['website'] = $this->createField('website', 'Website');
        $fields['language'] = $this->createField('language', 'Language');
        $fields['username'] = $this->createField('username', 'Username');
        $fields['dateAdded'] = $this->createField('dateAdded', 'Date Added');
        $fields['subscribed'] = $this->createField('subscribed', 'Subscribed flag');

        return $fields;
    }

    /**
     * Exports list of member groups and newsletter channels
     * @return array
     */
    private function getCustomerGroups()
    {
        $groups = $this->n2goModel->getMemberGroups();
        $channels  = $this->n2goModel->getNewsletterGroups();

        return array('groups' => array_merge($groups, $channels));
    }

    /**
     * Returns number of members in a group
     * @return array
     * @throws \Exception
     */
    private function getCustomersCount()
    {
        $groupId = \Input::post('groupId');
        $subscribed = \Input::post('subscribed');

        return array('count' => $this->n2goModel->getCustomerCount($groupId, $subscribed));
    }

    /**
     * Exports members and subscribers
     */
    private function getCustomers()
    {
        $groupId = \Input::post('groupId');
        $subscribed = \Input::post('subscribed');
        $fields = \Input::post('fields');
        $emails = \Input::post('emails');
        $limit = \Input::post('limit');
        $offset = \Input::post('offset');

        if (!$groupId) {
            throw new \Exception('Group Id parameter missing.');
        }

        if (($limit && filter_var($limit, FILTER_VALIDATE_INT) === false) ||
            ($offset && filter_var($offset, FILTER_VALIDATE_INT) === false)) {
            throw new \Exception('Limit and offset parameters must be integers!');
        }

        if (empty($fields)) {
            $fields = array_keys($this->getCustomerFields());
        }

        return array(
            'customers' => $this->n2goModel->getCustomers($groupId, $subscribed, $fields, $limit, $offset, $emails),
        );
    }

    /**
     * Changes subscriber active status
     * @return array
     * @throws \Exception
     */
    private function changeSubscriberStatus()
    {
        $email = \Input::post('email');
        $status = \Input::post('status');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \Exception('Email address is not valid!');
        }

        $subscribers = \NewsletterRecipientsModel::findByEmail($email);
        if ($subscribers->count() == 0) {
            throw new \Exception("Subscriber with email $email not found!");
        }

        /** @var \NewsletterRecipientsModel $subscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->active = ($status ? 1 : 0);
            $subscriber->save();
        }

        return array();
    }

    /**
     * @param $id
     * @param $name
     * @param string $description
     * @param string $type
     * @return array
     */
    private function createField($id, $name, $description = '', $type = 'String')
    {
        return array(
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'type' => $type,
        );
    }

}