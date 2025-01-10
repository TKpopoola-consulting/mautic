<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\LeadBundle\EventListener\GlobalSearchTrait;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    use GlobalSearchTrait;

    public function __construct(
        private NotificationModel $model,
        private CorePermissions $security,
        private Environment $twig,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH => [
                ['onGlobalSearchWebNotification', 0],
                ['onGlobalSearchMobileNotification', 0],
            ],
        ];
    }

    public function onGlobalSearchWebNotification(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $filterDTO->setFilters([
            'where'  => [
                [
                    'expr' => 'eq',
                    'col'  => 'mobile',
                    'val'  => 0,
                ],
            ],
        ]);
        $results = $this->globalSearch->performSearch(
            $filterDTO,
            $this->model,
            '@MauticNotification/SubscribedEvents/Search/global-web.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.notification.notification.header', $results);
        }
    }

    public function onGlobalSearchMobileNotification(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $filterDTO->setFilters([
            'where'  => [
                [
                    'expr' => 'eq',
                    'col'  => 'mobile',
                    'val'  => 1,
                ],
            ],
        ]);
        $results = $this->globalSearch->performSearch(
            $filterDTO,
            $this->model,
            '@MauticNotification/SubscribedEvents/Search/global-mobile.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.notification.mobile_notification.header', $results);
        }
    }
}
