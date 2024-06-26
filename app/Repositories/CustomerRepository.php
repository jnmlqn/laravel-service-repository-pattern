<?php

namespace App\Repositories;

use App\Helpers\Database;
use App\Entities\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @var  Database
     */
    private Database $db;

    /**
     * @var  EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param  Database $db
     * @param  EntityManagerInterface $entityManager
     * 
     * @return void
     */
    public function __construct(
        Database $db,
        EntityManagerInterface $entityManager
    ) {
        $this->db = $db;
        $this->entityManager = $entityManager;
    }

    /**
     * @return bool
     */
    public function import(): bool
    {
        $response = Http::get('https://randomuser.me/api/?results=100&nat=au');

        if ($response->ok()) {
            $data = json_decode($response->body(), true);
            $users = $data['results'];

            foreach ($users as $user) {
                $customer = $this->db->findBy(
                    Customer::class,
                    [
                        'email' => $user['email']
                    ]
                );

                if (is_null($customer)) {
                    $customer = new Customer();
                }

                $customer->setFullname($this->getFullName($user['name']));
                $customer->setEmail($user['email']);
                $customer->setUsername($user['login']['username']);
                $customer->setPassword($user['login']['md5']);
                $customer->setGender($user['gender']);
                $customer->setCountry($user['location']['country']);
                $customer->setCity($user['location']['city']);
                $customer->setPhone($user['phone']);

                $this->entityManager->persist($customer);
                $this->entityManager->flush();
            }

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function list(): array
    {
        return $this->db->findAll(Customer::class);
    }

    /**
     * @param  string  $id
     * @return Customer|null
     */
    public function show(string $id): ?Customer
    {
        return $this->db->findBy(
            Customer::class,
            [
                'id' => $id
            ]
        );
    }

    /**
     * @param  array  $name
     * 
     * @return string
     */
    private function getFullName(array $name): string
    {
        return $name['first'] . ' ' . $name['last'];
    }
}
