# 기여 가이드

Mublo Framework에 기여해 주셔서 감사합니다.

## 시작하기

1. 저장소를 포크합니다
2. 로컬에 클론합니다
3. `develop` 브랜치에서 작업 브랜치를 생성합니다

```bash
git checkout develop
git pull origin develop
git checkout -b feature/my-feature
```

## 브랜치 전략

Git Flow의 경량 버전을 따릅니다.

- **`main`** — 릴리스된 안정 버전. 직접 푸시 금지. 메인테이너만 머지 가능
- **`develop`** — 다음 릴리스를 위한 통합 브랜치. 모든 작업의 베이스
- **`feature/<name>`** — 새 기능. `develop`에서 분기, `develop`으로 PR
- **`fix/<name>`** — 버그 수정. `develop`에서 분기, `develop`으로 PR
- **`hotfix/<name>`** — 릴리스 후 긴급 패치. `main`에서 분기, `main`으로 PR (메인테이너 전용)

릴리스 시점에 메인테이너가 `develop` → `main` PR을 만들고 머지 후 버전 태그(`v1.0.1`)를 부여합니다.

> ⚠️ **외부 컨트리뷰터는 `main`이 아닌 `develop`을 베이스로 PR을 보내 주세요.** `main`으로 들어온 PR은 base 브랜치 변경을 요청드립니다. `main`으로의 머지는 메인테이너 권한으로 제한되어 있습니다.

## 개발 환경

- PHP 8.2 이상
- MySQL 5.7 이상 (또는 MariaDB 10.3 이상)
- Composer
- 필수 PHP 확장: pdo, pdo_mysql, mysqli, mbstring, openssl, json, curl, fileinfo

```bash
composer install
composer test
```

로컬 개발 시 `config/`, `storage/`, `.env`는 설치 과정 또는 환경별 설정으로 준비합니다. 설치 과정은 [설치 가이드](docs/user-guide/installation.md)를 참고하세요.

## 코드 스타일

- 클래스명: PascalCase (`MemberService`)
- 메서드명: camelCase (`findById`)
- 한 클래스 한 책임
- Controller에 비즈니스 로직 금지
- Service는 Result 객체 반환

## 커밋 메시지

한글로 작성합니다.

```
feat: 회원 포인트 자동 지급 기능 추가
fix: 게시판 권한 검사 누락 수정
refactor: BoardService 메서드 분리
docs: 설치 가이드 업데이트
test: MemberService 단위 테스트 추가
```

## Pull Request

1. `develop` 브랜치에서 최신 코드를 가져옵니다
2. `feature/` 또는 `fix/` 브랜치에서 작업합니다
3. 테스트를 통과시킵니다 (`composer test`)
4. **base 브랜치를 `develop`으로 지정**하여 PR을 생성합니다

저장소에 [PR 템플릿](.github/pull_request_template.md)이 준비되어 있습니다. PR 생성 시 자동으로 표시됩니다.

머지는 **squash merge**만 허용되며, 머지 후 작업 브랜치는 자동으로 삭제됩니다. 머지 커밋의 제목은 PR 제목, 본문은 PR 본문이 사용되므로 PR 작성 시 둘 다 신경 써 주세요.

### PR 체크리스트

- [ ] 테스트 통과
- [ ] 기존 테스트 깨뜨리지 않음
- [ ] 코드 스타일 준수
- [ ] 관련 문서 업데이트 (필요 시)

## 이슈 등록

버그 리포트와 기능 요청에 각각 [이슈 템플릿](.github/ISSUE_TEMPLATE/)이 준비되어 있습니다.

- **버그**: "Bug Report" 템플릿 사용
- **기능 요청**: "Feature Request" 템플릿 사용
- **보안 취약점**: 공개 이슈 대신 [SECURITY.md](SECURITY.md) 절차를 따라 주세요

## 모듈 추가/변경 시 주의

- 새 패키지나 플러그인을 추가하면 `README`, `docs/README`, 해당 모듈 README를 함께 갱신합니다.
- 기존 모듈의 구조를 바꾸는 변경은 관련 문서도 함께 수정합니다.

## 질문이 있다면

이슈를 생성해 주세요.
