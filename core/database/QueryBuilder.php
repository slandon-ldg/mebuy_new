<?php
/**
 * Created by PhpStorm.
 * User: slandon
 * Date: 27/08/2018
 * Time: 15:17
 */

class QueryBuilder
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * QueryBuilder constructor.11
     * @param $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param $table
     * @return array
     */
    public function selectAllFrom($table)
    {
        $statement = $this->pdo->prepare("select * from {$table}");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * @param $table
     * @param $parameters
     */
    public function insert($table, $parameters)
    {
        $sql = sprintf(
        /** @lang text */
            'insert into %s (%s) values (%s)',
            $table,
            implode(', ', array_keys($parameters)),
            ':' . implode(', :', array_keys($parameters))
        );
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        } catch (\Exception $e) {
            echo "Error with SQL statement";
        }
    }

    /**
     * @param $user_email
     * @return bool
     */
    public function checkUserRegistrationDetails($user_email)
    {
        if (!empty($user_email)) {
            $statement = $this->pdo->prepare("select * from users where users.email_address = '" . $user_email . "'");
            $statement->execute();

            $statement->fetchAll(PDO::FETCH_CLASS);
            $count = $statement->rowCount();

            if ($count === 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $user_email
     * @param $user_pword
     * @return bool
     */
    public function checkUserLoginDetails($user_email, $user_pword)
    {
        if (!empty($user_email) || !empty($user_pword)) {
            $statement = $this->pdo->prepare("select * from users where users.email_address = '" . $user_email . "'");
            $statement->execute();

            $row   = $statement->fetch(PDO::FETCH_ASSOC);
            $count = $statement->rowCount();

            if ($count === 1) {
                if (password_verify($user_pword, $row['password'])) {
                    session_start();
                    $_SESSION['user_id']    = $row['user_id'];
                    $_SESSION['first_name'] = $row['first_name'];
                    $_SESSION['last_name']  = $row['last_name'];
                    $_SESSION['user_email'] = $row['email_address'];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $fname
     * @param $lname
     * @param $email
     * @return bool
     */
    public function updateUserPersonalDetails($fname, $lname, $email)
    {
        session_start();
        if (!empty($fname) || !empty($lname) || !empty($email)) {
            $statement = $this->pdo->prepare("update users set users.first_name = '" . $fname . "', users.last_name = '" . $lname . "', users.email_address = '" . $email . "' where users.user_id = '" . $_SESSION['user_id'] . "'");

            $statement->execute();

            $_SESSION['first_name'] = $fname;
            $_SESSION['last_name']  = $lname;
            $_SESSION['user_email'] = $email;

            return true;
        }
        return false;
    }

    /**
     * @param $street
     * @param $city
     * @param $postcode
     * @param $country
     * @param $phonenumber
     * @param $active_shipping_address
     * @return bool
     */
    public function updateUserAddressDetails($street, $city, $postcode, $country, $phonenumber, $active_shipping_address)
    {
        session_start();
        if (!empty($street)
            && !empty($city)
            && !empty($postcode)
            && !empty($country)
            && !empty($phonenumber)
        ) {
            if ($active_shipping_address === 1) {
                // First check if there is an active address, if so then change it to inactive
                $checkActiveAddress = $this->pdo->prepare("
                update customer_address set customer_address.user_id = '" . $_SESSION['user_id'] . "', customer_address.active_address = '0'
                ");
                $checkActiveAddress->execute();

                // Then add the new address regardless
                $addActiveAddress = $this->pdo->prepare("
                    insert into customer_address (user_id, street_address, city, postcode, country, phone_number, active_address) values ('" . $_SESSION['user_id'] . "', '" . $street . "', '" . $city . "', '" . $postcode . "', '" . $country . "', '" . $phonenumber . "', '1')
                ");
                $addActiveAddress->execute();
            } else {
                $addAddress = $this->pdo->prepare("
                    insert into customer_address (user_id, street_address, city, postcode, country, phone_number, active_address) values ('" . $_SESSION['user_id'] . "', '" . $street . "', '" . $city . "', '" . $postcode . "', '" . $country . "', '" . $phonenumber . "', '0')
                ");
                $addAddress->execute();
            }
            return true;
        }
        return false;
    }

    public function updateUserBillingDetails($street, $city, $postcode, $country, $phonenumber, $active_billing_address)
    {
        return false;
    }

    /**
     * @return array|bool
     */
    public function getUserActiveShippingDetails()
    {
        if (!session_id()) session_start();
        if (!empty($_SESSION['user_id'])) {
            $activeAddressStatement = $this->pdo->prepare("
                select * from customer_address where customer_address.user_id = '" . $_SESSION['user_id'] . "' and customer_address.active_address = '1'
            ");
            $activeAddressStatement->execute();
            $activeAddressRow      = $activeAddressStatement->fetch(PDO::FETCH_ASSOC);
            $activeAddressRowCount = $activeAddressStatement->rowCount();

            if ($activeAddressRowCount === 1) {
                return [
                    'street_address' => $activeAddressRow['street_address'],
                    'city'           => $activeAddressRow['city'],
                    'postcode'       => $activeAddressRow['postcode'],
                    'country'        => $activeAddressRow['country'],
                    'phone_number'   => $activeAddressRow['phone_number'],
                ];
            }
        }
        return false;
    }

}