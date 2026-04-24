<?php

namespace Mublo\Contract\Fcm;

use Mublo\Core\Result\Result;

/**
 * FCM 범용 메시지 디스패처.
 *
 * 4가지 주소 지정 모드:
 *   1) byPhone  — 전화번호 → 바인딩 → 기기 → app_type 설치 → FCM push
 *   2) byDevice — 특정 기기의 app_type 설치로 직접 push (관리 UI, 테스트 발송 등)
 *   3) byMember — 회원이 소유한 활성 기기의 특정 app_type (Web Push·개인앱 알림)
 *   4) byTopic  — FCM 토픽 구독자 전체 (게시판 새글 알림 등 브로드캐스트)
 *
 * action 은 앱이 처리할 지시어: 'send_sms' | 'send_kakao' | 'save_contact' | 'show_notification' | custom.
 * payload 는 앱이 수신할 데이터. FCM 특성상 모든 값이 string 으로 변환돼 전송됨.
 */
interface FcmMessageServiceInterface
{
    /**
     * 전화번호 타깃 — 바인딩된 기기의 app_type 앱에 푸시.
     * 바인딩 없으면 failure.
     */
    public function dispatchToPhone(
        int $domainId,
        string $phone,
        string $appType,
        string $action,
        array $payload
    ): Result;

    /**
     * 기기·앱 타깃 — 관리자 테스트 발송·명시적 기기 지정.
     */
    public function dispatchToDevice(
        int $deviceId,
        string $appType,
        string $action,
        array $payload
    ): Result;

    /**
     * 회원 타깃 — 해당 회원 소유의 모든 활성 기기에서 app_type 앱 설치로 브로드캐스트.
     * Web Push(app_type='web') · 개인 알림앱 등 multi-device 수신자에 사용.
     *
     * @return Result data: ['sent' => int, 'failed' => int, 'installations' => int]
     */
    public function dispatchToMember(
        int $domainId,
        int $memberId,
        string $appType,
        string $action,
        array $payload
    ): Result;

    /**
     * 토픽 타깃 — 구독자 전체 브로드캐스트.
     */
    public function dispatchToTopic(
        string $topic,
        string $action,
        array $payload
    ): Result;
}
