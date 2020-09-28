<?php


namespace Source\Models\CoffeeApp;


use Source\Core\Model;
use Source\Models\User;

/**
 * Class AppInvoice
 * @package Source\Models\CoffeeApp
 */
class AppInvoice extends Model
{
    /**
     * AppInvoice constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "app_invoices",
            ["id"],
            ["user_id", "wallet_id", "category_id", "description", "type", "value", "due_at", "repeat_when"]);
    }


    /**
     * @param User $user
     * @param int $afterMonths
     */
    public function fixed(User $user, int $afterMonths = 1): void
    {
        //fuscando as faturas fixas
        $fixed = $this->find("user_id = :user AND status = 'paid' AND type IN ('fixed_income', 'fixed_expense')",
            "user={$user->id}")->fetch(true);

        //verificando se teve algum retornoç
        if (!$fixed) {
            return;
        }

        //criando as demais faturas atravez das fixas
        foreach ($fixed as $fixedItem) {
            $invoice = $fixedItem->id;
            $start = new \DateTime($fixedItem->due_at);
            $end = new \DateTime("+{$afterMonths}month");

            if ($fixedItem->period == "month"){
                $interval = new \DateTime("P1M");
            }
            if ($fixedItem->period == "threeMonth"){
                $interval = new \DateTime("P3M");
            }
            if ($fixedItem->period == "sixMonth"){
                $interval = new \DateTime("P6M");
            }

            if ($fixedItem->period == "year"){
                $interval = new \DateTime("P1Y");
            }


        }

        var_dump($fixed);

    }


    /**
     * @param User $user
     * @param string $type
     * @param array|null $filter
     * @param int|null $limit
     * @return array|null
     */
    public function filter(User $user, string $type, ?array $filter, ?int $limit = null): ?array
    {
        //pegando o status e verificando se for igual a pago "AND status = 'paid'"
        $status = (!empty($filter["status"]) && $filter["status"] == "paid" ? "AND status = 'paid'" :
            (!empty($filter['status']) == "unpaid" ? "AND status = 'unpaid'" : null));
        $category = (!empty($filter['category']) && $filter['category'] != "all" ? "AND category_id = '{$filter['category']}'" : null);

        $due_year = (!empty($filter['date']) ? explode("-", $filter['date'])[1] : date("Y"));
        $due_month = (!empty($filter['date']) ? explode("-", $filter['date'])[0] : date("m"));
        $due_at = "AND (year(due_at) = '{$due_year}' AND month(due_at) = '{$due_month}')";

        $due = $this->find("user_id = :user AND type = :type {$status} {$category} {$due_at}",
            "user={$user->id}&type={$type}")
            ->order("day(due_at) ASC");

        if ($limit) {
            $due->limit($limit);
        }

        return $due->fetch(true);
    }


    /**
     * @return AppCategory
     */
    public function category(): AppCategory
    {
        return (new AppCategory())->findById($this->category_id);
    }

}