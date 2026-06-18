# TODO — Laravel UI + Node (NextAuth+Prisma+Socket.IO)

## Phase 0: Repo reconnaissance
- [ ] Inspect existing frontend API usage (which endpoints, token headers, etc.).
- [ ] Inspect existing DB schema assumptions (from current PHP models/migrations).

## Phase 1: Set up Next.js (NextAuth + Prisma)
- [ ] Create `Talentcircle/next/` Next.js project.
- [ ] Add Prisma schema + client.
- [ ] Add NextAuth configuration (credentials/google/otp as appropriate).
- [ ] Add API route handlers expected by the frontend (match, sessions, profile, points, chat if not purely socket).

## Phase 2: Keep/port Socket.IO
- [ ] Port/replace `Talentcircle/server.js` to an authenticated Socket.IO server (Next.js custom server or separate Node process).
- [ ] Ensure socket auth uses NextAuth session/JWT.
- [ ] Persist chat messages/conversations using Prisma.

## Phase 3: Create Laravel UI skeleton
- [ ] Add a Laravel app (inside `Talentcircle/` or `Talentcircle/backend-laravel/`).
- [ ] Add `routes/web.php` + Blade layout.
- [ ] Convert at least: home, signin, signup, dashboard, profile into Blade views.

## Phase 4: Wire frontend to new backends
- [ ] Update `api-base.js` to point to Node/Next.js base URL (port chosen: 3000).
- [ ] Replace token-based auth headers with NextAuth session/JWT flow (or a backend-for-frontend endpoint).

## Phase 5: Retire old PHP API/router
- [ ] Disable `Talentcircle/router.php` static delegation for `/api/*` once Laravel is live.
- [ ] Retire `Talentcircle/backend/routes/api.php` usage.

## Phase 6: QA
- [ ] Smoke test: register/login -> fetch profile -> fetch dashboard summary -> chat messages.
- [ ] Validate AI match endpoint compatibility and token deduction.

