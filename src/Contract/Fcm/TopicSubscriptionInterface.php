<?php

namespace Mublo\Contract\Fcm;

/**
 * FCM 토픽 구독 관리.
 *
 * 사용 시나리오:
 *   - 게시판 새글 알림: subscribe('board_{id}_new_post', $installation)
 *   - 쇼핑몰 프로모션: subscribe('shop_promo', $installation)
 *   - 운영 긴급 공지: subscribe('ops_emergency', $installation)
 *
 * 호출자(Board/Shop 등 패키지)는 installation_id 가 없는 경우가 많음 —
 * 일반적으로 member_id 로 해당 회원의 모든 앱 설치를 조회해 bulk subscribe.
 */
interface TopicSubscriptionInterface
{
    public function subscribe(int $domainId, string $topic, int $installationId): void;
    public function unsubscribe(int $domainId, string $topic, int $installationId): void;

    /**
     * 회원 소유 기기의 특정 app_type 설치를 모두 구독 처리.
     * 예: Board 에서 회원이 "이 게시판 알림 받기" 토글 시.
     */
    public function subscribeMember(int $domainId, string $topic, int $memberId, string $appType = 'sms'): int;

    public function unsubscribeMember(int $domainId, string $topic, int $memberId, string $appType = 'sms'): int;

    /**
     * 해당 토픽에 구독된 installation_id 목록.
     * dispatchToTopic 내부에서 쓰일 수도 있으나, FCM 토픽 기능 자체가
     * fan-out 을 해주므로 주로 통계·관리용.
     *
     * @return int[] installation_ids
     */
    public function subscribersOf(int $domainId, string $topic): array;
}
