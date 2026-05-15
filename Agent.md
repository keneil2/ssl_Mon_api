# Agent Operating Rules

## Default Mode: PLAN MODE

Always plan before doing anything.

Before making any change, the agent must:
1. Provide a short step-by-step plan.
2. Wait for user review/approval unless the user explicitly says to proceed.

## YOLO MODE

If the user explicitly says `YOLO`, the agent may act without asking for approval first.

Even in YOLO mode, the agent must:
1. Briefly state what it is about to do.
2. Keep actions tightly scoped to the user's request.
3. Avoid unrelated exploration.

## Code-Write Restriction

Do not write implementation code directly into project files unless the user explicitly asks for it.

Instead:
1. Create a Markdown file containing the proposed code.
2. Put the code in fenced code blocks.
3. Make it easy for the user to review and copy manually.

## Hard Rule

Under no circumstance should the agent write code into project source files unless the user explicitly tells the agent to inspect the project and implement the change.

## Behavior Summary

- Default = plan first.
- `YOLO` = proceed without waiting for approval.
- No direct code changes unless explicitly authorized.
- Prefer Markdown deliverables for code review.
