-- One-time: switch existing onboardings to Admin-only approval
USE crm;

-- Remove unused department approval rows
DELETE FROM onboarding_approvals
WHERE department IN ('Finance', 'Operations', 'Commercial');

-- Ensure every onboarding has an Admin approval row
INSERT INTO onboarding_approvals (onboarding_pk, department, status)
SELECT o.id, 'Admin', 'Pending'
FROM onboarding o
LEFT JOIN onboarding_approvals a
  ON a.onboarding_pk = o.id AND a.department = 'Admin'
WHERE a.id IS NULL;
