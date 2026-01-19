<?php

namespace App\Core;

use PDO;
use Exception;
use DateTime;

/**
 * PlanManager
 * Handles the logic for project subscriptions, plan types, and billing cycles.
 */
class PlanManager
{
    /**
     * Calculates the next billing date based on a start date and plan type.
     * 
     * @param string $startDate
     * @param string $planType (monthly, quarterly, semiannual, annual)
     * @return string
     */
    public static function calculateNextBillingDate($startDate, $planType)
    {
        $date = new DateTime($startDate);
        switch ($planType) {
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'quarterly':
                $date->modify('+3 months');
                break;
            case 'semiannual':
                $date->modify('+6 months');
                break;
            case 'annual':
                $date->modify('+1 year');
                break;
            default:
                throw new Exception("Invalid plan type: $planType");
        }
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Switches the plan for a project and records the change.
     * 
     * @param int $projectId
     * @param string $newPlanType
     * @param string|null $activationDate Optional custom start date
     */
    public static function switchPlan($projectId, $newPlanType, $activationDate = null)
    {
        $db = Database::getInstance()->getConnection();

        // Get current plan info
        $stmt = $db->prepare("SELECT plan_type FROM project_plans WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $oldPlan = $stmt->fetchColumn() ?: 'none';

        // Record history
        $stmt = $db->prepare("INSERT INTO subscription_history (project_id, old_plan, new_plan) VALUES (?, ?, ?)");
        $stmt->execute([$projectId, $oldPlan, $newPlanType]);

        // Re-initialize billing dates
        $start = $activationDate ?: Auth::getCurrentTime();
        $next = self::calculateNextBillingDate($start, $newPlanType);

        $stmt = $db->prepare("REPLACE INTO project_plans (project_id, plan_type, start_date, next_billing_date, status) 
                             VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$projectId, $newPlanType, $start, $next]);
    }

    /**
     * Checks if a project has an active subscription.
     * 
     * @param int $projectId
     * @return bool
     */
    public static function isActive($projectId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT status, next_billing_date FROM project_plans WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $plan = $stmt->fetch();

        if (!$plan || $plan['status'] !== 'active') {
            return false;
        }

        // Check if billing date has passed (simple check)
        return strtotime($plan['next_billing_date']) > time();
    }
}
