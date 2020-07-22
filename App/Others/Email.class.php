<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @brief email class to send data to any email address in html form or/and non html form data
 */
class Email
{
    /**
     * @brief instance of phpmailer
     */
    protected static $Mail;

    /**
     * @brief php mailer setting: host
     */
    protected static $_Host = "mail.domain.com";

    public static function setHost($host)
    {
        self::$_Host = $host;
    }

    /**
     * @smtp auto auth tls
     */
    protected static $_SMTPAutoTLS = false;

    public static function setSMTPAutoTLS($bool)
    {
        self::$_SMTPAutoTLS = !(!$bool);
    }

    /**
     * @brief php mailer setting: smtp auth
     */
    protected static $_SMTP_AUTH = true;

    public static function setSMTPAuth($bool)
    {
        self::$_SMTP_AUTH = !(!$bool);
    }
    /**
     * @brief php mailer setting: user name
     */
    protected static $_UserName = "noreply@domain.com";

    public static function setUserName($username)
    {
        self::$_UserName = $username;
    }

    /**
     * @brief php mailer setting: password
     */
    protected static $_PassWord = "**********"; //jvuyykwcabvpmbtj

    public static function setPassword($password)
    {
        self::$_PassWord = $password;
    }

    /**
     * @brief php mailer setting: SMTPSecure
     */
    protected static $_SMTPSecure = '';

    public static function setSMTPSecure($sec)
    {
        self::$_SMTPSecure = $sec;
    }


    /**
     * @brief php mailer setting: port
     */
    protected static $_Port = 587;

    public static function setPort($port)
    {
        self::$_Port = $port;
    }

    /**
     * @brief php mailer setting: from
     */
    protected static $_From = "noreply@domain.com";

    public static function setFrom($from)
    {
        self::$_From = $from;
    }
    /**
     * @brief php mailer setting: from name
     */
    protected static $_FromName = "No-Reply";

    public static function setName($name)
    {
        self::$_FromName = $name;
    }

    /**
     * @brief store sending email exception error
     */
    protected static $_Exception = "";


    /**
     * @brief get exception error of email
     * @return string
     */
    public static function GetException()
    {
        return self::$_Exception;
    }

    /**
     * get the phpmailer object
     * @return PHPMailer
     */
    public static function GetPHPMailer()
    {
        if (is_null(self::$Mail)) {
            self::$Mail = new PHPMailer();
        }

        self::$Mail->ClearAddresses();
        self::$Mail->ClearAttachments();
        self::$Mail->ClearReplyTos();
        self::$Mail->ClearAllRecipients();
        self::$Mail->ClearCustomHeaders();

        return self::$Mail;
    }

    /**
     * @brief send email function
     * @param[in] string $Subject
     * @param[in] array|string $To
     * @param[in] string $Body the body content
     * @param[in] string $Alt_Body the alt content to show in non html view
     * @param[in] array $Attachments
     * @param[in] array $replyTo
     * @param[in] array $CC
     * @param[in] array $BCC
     * @return bool
     */
    public static function Send(
        $Subject,
        $To,
        $Body,
        $Alt_Body    = "",
        $Attachments = array(),
        $replyTo     = array(),
        $CC          = array(),
        $BCC         = array()
    ) {
        self::$_Exception = "";
        try {
            self::GetPHPMailer();

            self::$Mail->SMTPDebug = 0;
            self::$Mail->isSMTP();
            self::$Mail->Host = self::$_Host;
            self::$Mail->SMTPAuth = self::$_SMTP_AUTH;
            self::$Mail->Username = self::$_UserName;
            self::$Mail->Password = self::$_PassWord;
            self::$Mail->SMTPSecure = self::$_SMTPSecure;
            self::$Mail->Port = self::$_Port;
            self::$Mail->SMTPAutoTLS = self::$_SMTPAutoTLS;
            self::$Mail->CharSet = 'UTF-8';
            self::$Mail->Encoding = 'base64';

            self::$Mail->SetFrom(self::$_From, self::$_FromName);

            if (gettype($To) != "array") {
                $To = array($To);
            }

            if (gettype($Attachments) == "array") {
                foreach ($Attachments as $key => &$val) {
                    if (gettype($val) !== "string") {
                        continue;
                    }

                    if (is_numeric($key)) {
                        self::$Mail->addAttachment($val);
                    } else {
                        self::$Mail->addAttachment($key, $val);
                    }
                }
            }

            if (gettype($replyTo) == "array") {
                foreach ($replyTo as $key => &$val) {
                    if (gettype($val) !== "string") {
                        continue;
                    }

                    if (is_numeric($key)) {
                        self::$Mail->addReplyTo($val);
                    } else {
                        self::$Mail->addReplyTo($key, $val);
                    }
                }
            }

            if (gettype($CC) == "array") {
                foreach ($CC as $key => &$val) {
                    if (gettype($val) !== "string") {
                        continue;
                    }

                    if (is_numeric($key)) {
                        self::$Mail->addCC($val);
                    } else {
                        self::$Mail->addCC($key, $val);
                    }
                }
            }

            if (gettype($BCC) == "array") {
                foreach ($BCC as $key => &$val) {
                    if (gettype($val) !== "string") {
                        continue;
                    }

                    if (is_numeric($key)) {
                        self::$Mail->addBCC($val);
                    } else {
                        self::$Mail->addBCC($key, $val);
                    }
                }
            }

            foreach ($To as $key => &$val) {
                if (gettype($val) !== "string") {
                    continue;
                }

                if (is_numeric($key)) {
                    self::$Mail->addAddress($val);
                } else {
                    self::$Mail->addAddress($key, $val);
                }
            }

            self::$Mail->isHTML(true);
            self::$Mail->Subject = $Subject;
            self::$Mail->Body = ($Body);
            self::$Mail->AltBody = $Alt_Body;
            self::$Mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            self::$_Exception .= $e->getMessage() . '\n';
        } catch (Exception $ee) {
            self::$_Exception .= $ee->getMessage() . '\n';
        }
        return false;
    }
}
