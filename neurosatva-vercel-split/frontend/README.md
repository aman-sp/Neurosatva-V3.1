# Neurosatva Frontend

This folder is the Vercel-ready frontend for the split architecture.

## What it expects

- A PHP backend exposed over HTTPS
- An API base URL in `NEXT_PUBLIC_API_BASE_URL`
- The backend env var `FRONTEND_URL` set to this frontend origin
- MySQL/MariaDB still managed by the backend host

## Local development

1. Install dependencies.
2. Copy `.env.example` to `.env.local`.
3. Set `NEXT_PUBLIC_API_BASE_URL` to your backend URL.
4. Set `FRONTEND_URL` on the PHP backend to the Vercel origin.
4. Run `npm run dev`.

## Vercel setup

- Set the project root to this `frontend` folder.
- Add `NEXT_PUBLIC_API_BASE_URL` in Vercel environment variables.
- Deploy this frontend separately from the PHP backend.
