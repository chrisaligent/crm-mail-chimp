<?php

namespace OroCRM\Bundle\MailChimpBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use OroCRM\Bundle\CampaignBundle\Model\EmailCampaignStatisticsConnector;
use OroCRM\Bundle\MailChimpBundle\Entity\MemberActivity;

class UpdateEmailCampaignStatistics extends AbstractMarketingListEntitiesAction
{
    /**
     * @var EmailCampaignStatisticsConnector
     */
    protected $campaignStatisticsConnector;

    /**
     * @param EmailCampaignStatisticsConnector $campaignStatisticsConnector
     */
    public function setCampaignStatisticsConnector($campaignStatisticsConnector)
    {
        $this->campaignStatisticsConnector = $campaignStatisticsConnector;
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowed($context)
    {
        $isAllowed = false;
        if ($context instanceof EntityAwareInterface) {
            $entity = $context->getEntity();
            if ($entity instanceof MemberActivity) {
                $mailChimpCampaign = $entity->getCampaign();
                $isAllowed = $mailChimpCampaign
                    && $mailChimpCampaign->getEmailCampaign()
                    && $mailChimpCampaign->getStaticSegment()
                    && $mailChimpCampaign->getStaticSegment()->getMarketingList();
            }
        }

        return $isAllowed && parent::isAllowed($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->updateStatistics($context->getEntity());
    }

    /**
     * @param MemberActivity $memberActivity
     */
    protected function updateStatistics(MemberActivity $memberActivity)
    {
        $mailChimpCampaign = $memberActivity->getCampaign();
        $emailCampaign = $mailChimpCampaign->getEmailCampaign();
        $marketingList = $mailChimpCampaign->getStaticSegment()->getMarketingList();

        $relatedEntities = $this->getMarketingListEntitiesByEmails($marketingList, [$memberActivity->getEmail()]);
        foreach ($relatedEntities as $relatedEntity) {
            $emailCampaignStatistics = $this->campaignStatisticsConnector->getStatisticsRecord(
                $emailCampaign,
                $relatedEntity
            );

            $this->incrementStatistics($memberActivity, $emailCampaignStatistics);
        }
    }

    /**
     * @param MemberActivity $memberActivity
     * @param EmailCampaignStatistics $emailCampaignStatistics
     */
    protected function incrementStatistics(
        MemberActivity $memberActivity,
        EmailCampaignStatistics $emailCampaignStatistics
    ) {
        switch ($memberActivity->getAction()) {
            case MemberActivity::ACTIVITY_SENT:
                $marketingListItem = $emailCampaignStatistics->getMarketingListItem();
                $marketingListItem->setLastContactedAt($memberActivity->getActivityTime());
                $marketingListItem->setContactedTimes((int)$marketingListItem->getContactedTimes() + 1);
                break;
            case MemberActivity::ACTIVITY_OPEN:
                $emailCampaignStatistics->incrementOpenCount();
                break;
            case MemberActivity::ACTIVITY_CLICK:
                $emailCampaignStatistics->incrementClickCount();
                break;
            case MemberActivity::ACTIVITY_BOUNCE:
                $emailCampaignStatistics->incrementBounceCount();
                break;
            case MemberActivity::ACTIVITY_ABUSE:
                $emailCampaignStatistics->incrementAbuseCount();
                break;
            case MemberActivity::ACTIVITY_UNSUB:
                $emailCampaignStatistics->incrementUnsubscribeCount();
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->campaignStatisticsConnector) {
            throw new \InvalidArgumentException('EmailCampaignStatisticsConnector is not provided');
        }

        return $this;
    }
}