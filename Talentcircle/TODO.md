# TODO

- [x] Inspect relevant backend/frontend files to locate how skills list and quiz are implemented.
- [x] Verify backend routes exist for skills and quiz (`/api/skills`, `/api/skills/search`, `/api/quiz/start/:skillId`, `/api/quiz/submit`).
- [x] Check `Talentcircle/src/api-base.js` for correct `window.apiUrl` base origin.
- [x] Update `Talentcircle/user-profile.html` to replace the hardcoded `quizBank` with DB-driven quiz from backend endpoints.
- [x] Keep dropdown behavior; use the selected skill name to look up `skill_id` via `/api/skills/search`.
- [x] Submit quiz answers to `/api/quiz/submit` using the user token.
- [ ] Runtime test checklist:
  - [ ] Open `user-profile.html` and confirm Skills dropdown populates.
  - [ ] Select a skill and confirm DB quiz questions render.
  - [ ] Answer all questions and confirm verification badge state updates.
  - [ ] Click “Continue” and confirm selected verified skill persists on `profile.html`.

