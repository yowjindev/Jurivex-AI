'use client'

import { useState, useEffect, Fragment } from 'react'
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query'
import { Button } from '@/components/ui/button'
import {
  listOrganizations,
  createOrganization,
  listInvitationCodes,
  generateInvitationCode,
} from '@/lib/api/superadmin'
import type { InvitationCode, OrgStats } from '@/types'

export default function SuperadminPage() {
  const queryClient = useQueryClient()

  const { data: orgs = [], isPending, isError } = useQuery({
    queryKey: ['superadmin', 'organizations'],
    queryFn:  listOrganizations,
  })

  const [showCreateOrg, setShowCreateOrg]         = useState(false)
  const [newOrgName, setNewOrgName]               = useState('')
  const [createOrgError, setCreateOrgError]       = useState('')
  const [expandedOrg, setExpandedOrg]             = useState<string | null>(null)
  const [generateFor, setGenerateFor]             = useState<OrgStats | null>(null)
  const [generateRole, setGenerateRole]           = useState<'admin' | 'manager' | 'staff'>('admin')
  const [generatedCode, setGeneratedCode]         = useState<InvitationCode | null>(null)
  const [genCodeError, setGenCodeError]           = useState('')

  const createOrg = useMutation({
    mutationFn: () => createOrganization(newOrgName.trim()),
    onSuccess: () => {
      setCreateOrgError('')
      queryClient.invalidateQueries({ queryKey: ['superadmin', 'organizations'] })
      setShowCreateOrg(false)
      setNewOrgName('')
    },
    onError: () => {
      setCreateOrgError('Failed to create organization. Please try again.')
    },
  })

  const { data: codes = [], isPending: codesLoading } = useQuery({
    queryKey: ['superadmin', 'invitation-codes', expandedOrg],
    queryFn:  () => listInvitationCodes(expandedOrg!),
    enabled:  expandedOrg !== null,
    placeholderData: keepPreviousData,
  })

  const genCode = useMutation({
    mutationFn: () => generateInvitationCode(generateFor!.id, generateRole),
    onSuccess: (code) => {
      setGenCodeError('')
      queryClient.invalidateQueries({ queryKey: ['superadmin', 'invitation-codes', generateFor!.id] })
      setGeneratedCode(code)
    },
    onError: () => {
      setGenCodeError('Failed to generate code. Please try again.')
    },
  })

  useEffect(() => {
    function handleEscape(e: KeyboardEvent) {
      if (e.key !== 'Escape') return
      if (showCreateOrg) { setShowCreateOrg(false); setNewOrgName(''); setCreateOrgError('') }
      if (generateFor)   { setGenerateFor(null); setGeneratedCode(null); setGenCodeError('') }
    }
    document.addEventListener('keydown', handleEscape)
    return () => document.removeEventListener('keydown', handleEscape)
  }, [showCreateOrg, generateFor])

  const totalMembers   = orgs.reduce((s, o) => s + o.member_count, 0)
  const totalDocuments = orgs.reduce((s, o) => s + o.document_count, 0)

  return (
    <div className="min-h-screen bg-background">
      <div className="max-w-6xl mx-auto px-6 py-10 space-y-8">

        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-foreground">Superadmin Panel</h1>
            <p className="text-muted-foreground text-sm mt-1">Manage organizations and invite codes</p>
          </div>
          <Button onClick={() => setShowCreateOrg(true)}>New Organization</Button>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-3 gap-4">
          {[
            { label: 'Organizations', value: orgs.length },
            { label: 'Total Members',   value: totalMembers },
            { label: 'Total Documents', value: totalDocuments },
          ].map(({ label, value }) => (
            <div key={label} className="rounded-xl border border-border bg-card p-5">
              <p className="text-3xl font-bold text-foreground">{value}</p>
              <p className="text-sm text-muted-foreground mt-1">{label}</p>
            </div>
          ))}
        </div>

        {/* Org Table */}
        <div className="rounded-xl border border-border bg-card overflow-hidden">
          <div className="px-6 py-4 border-b border-border">
            <h2 className="font-medium text-foreground">Organizations</h2>
          </div>

          {isPending && (
            <div className="px-6 py-8 text-center text-muted-foreground text-sm">Loading…</div>
          )}

          {isError && (
            <div className="px-6 py-8 text-center text-destructive text-sm">
              Failed to load organizations.
            </div>
          )}

          {!isPending && !isError && orgs.length === 0 && (
            <div className="px-6 py-8 text-center text-muted-foreground text-sm">
              No organizations yet.
            </div>
          )}

          {!isPending && !isError && orgs.length > 0 && (
            <table className="w-full text-sm">
              <thead className="bg-muted/40 text-muted-foreground">
                <tr>
                  <th className="text-left px-6 py-3 font-medium">Name</th>
                  <th className="text-right px-6 py-3 font-medium">Members</th>
                  <th className="text-right px-6 py-3 font-medium">Documents</th>
                  <th className="text-right px-6 py-3 font-medium">Flags</th>
                  <th className="text-right px-6 py-3 font-medium">Created</th>
                  <th className="px-6 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {orgs.map((org) => (
                  <Fragment key={org.id}>
                    <tr className="hover:bg-muted/20">
                      <td className="px-6 py-4 font-medium text-foreground">{org.name}</td>
                      <td className="px-6 py-4 text-right text-muted-foreground">{org.member_count}</td>
                      <td className="px-6 py-4 text-right text-muted-foreground">{org.document_count}</td>
                      <td className="px-6 py-4 text-right text-muted-foreground">{org.flag_count}</td>
                      <td className="px-6 py-4 text-right text-muted-foreground">
                        {new Date(org.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => setExpandedOrg(expandedOrg === org.id ? null : org.id)}
                            className="text-xs text-muted-foreground hover:text-foreground underline"
                          >
                            {expandedOrg === org.id ? 'Hide codes' : 'View codes'}
                          </button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => {
                              setGenerateFor(org)
                              setGenerateRole('admin')
                              setGeneratedCode(null)
                            }}
                          >
                            Generate code
                          </Button>
                        </div>
                      </td>
                    </tr>

                    {expandedOrg === org.id && (
                      <tr>
                        <td colSpan={6} className="px-6 py-4 bg-muted/20">
                          {codesLoading && (
                            <p className="text-sm text-muted-foreground">Loading codes…</p>
                          )}
                          {!codesLoading && codes.length === 0 && (
                            <p className="text-sm text-muted-foreground">No codes yet.</p>
                          )}
                          {!codesLoading && codes.length > 0 && (
                            <table className="w-full text-xs">
                              <thead className="text-muted-foreground">
                                <tr>
                                  <th className="text-left py-1 pr-6 font-medium">Code</th>
                                  <th className="text-left py-1 pr-6 font-medium">Role</th>
                                  <th className="text-left py-1 pr-6 font-medium">Status</th>
                                  <th className="text-left py-1 font-medium">Expires</th>
                                </tr>
                              </thead>
                              <tbody className="divide-y divide-border/50">
                                {codes.map((c) => (
                                  <tr key={c.id}>
                                    <td className="py-2 pr-6 font-mono tracking-widest text-foreground">{c.code}</td>
                                    <td className="py-2 pr-6 capitalize text-muted-foreground">{c.role}</td>
                                    <td className="py-2 pr-6">
                                      <span className={c.is_used ? 'text-muted-foreground' : 'text-green-600 dark:text-green-400'}>
                                        {c.is_used ? 'Used' : 'Active'}
                                      </span>
                                    </td>
                                    <td className="py-2 text-muted-foreground">
                                      {c.expires_at ? new Date(c.expires_at).toLocaleDateString() : '—'}
                                    </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          )}
                        </td>
                      </tr>
                    )}
                  </Fragment>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {/* Create Org Modal */}
      {showCreateOrg && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
          onClick={() => { setShowCreateOrg(false); setNewOrgName(''); setCreateOrgError('') }}
        >
          <div className="bg-card border border-border rounded-xl p-6 w-full max-w-sm shadow-xl" onClick={(e) => e.stopPropagation()}>
            <h3 className="font-semibold text-foreground mb-4">New Organization</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-foreground mb-1.5">
                  Organization name
                </label>
                <input
                  type="text"
                  value={newOrgName}
                  onChange={(e) => { setNewOrgName(e.target.value); setCreateOrgError('') }}
                  placeholder="Santos & Reyes Law Firm"
                  className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                />
                {createOrgError && (
                  <p className="text-destructive text-xs mt-1">{createOrgError}</p>
                )}
              </div>
              <div className="flex gap-3">
                <Button
                  variant="outline"
                  className="flex-1"
                  onClick={() => { setShowCreateOrg(false); setNewOrgName(''); setCreateOrgError('') }}
                >
                  Cancel
                </Button>
                <Button
                  className="flex-1"
                  disabled={!newOrgName.trim() || createOrg.isPending}
                  onClick={() => createOrg.mutate()}
                >
                  {createOrg.isPending ? 'Creating…' : 'Create'}
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Generate Code Modal */}
      {generateFor && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
          onClick={() => { setGenerateFor(null); setGeneratedCode(null); setGenCodeError('') }}
        >
          <div className="bg-card border border-border rounded-xl p-6 w-full max-w-sm shadow-xl" onClick={(e) => e.stopPropagation()}>
            <h3 className="font-semibold text-foreground mb-1">Generate Invite Code</h3>
            <p className="text-muted-foreground text-sm mb-4">{generateFor.name}</p>

            {generatedCode ? (
              <div className="space-y-4">
                <div className="rounded-lg bg-muted/40 border border-border p-4 text-center">
                  <p className="text-xs text-muted-foreground mb-1">Invite code</p>
                  <p className="text-2xl font-mono font-bold tracking-widest text-foreground">
                    {generatedCode.code}
                  </p>
                  <p className="text-xs text-muted-foreground mt-1 capitalize">
                    Role: {generatedCode.role}
                  </p>
                </div>
                <p className="text-xs text-muted-foreground text-center">
                  Share this code with the person joining {generateFor.name}.
                </p>
                <Button
                  className="w-full"
                  onClick={() => { setGenerateFor(null); setGeneratedCode(null); setGenCodeError('') }}
                >
                  Done
                </Button>
              </div>
            ) : (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-foreground mb-1.5">Role</label>
                  <select
                    value={generateRole}
                    onChange={(e) => setGenerateRole(e.target.value as typeof generateRole)}
                    className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  >
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                  </select>
                  {genCodeError && (
                    <p className="text-destructive text-xs mt-1">{genCodeError}</p>
                  )}
                </div>
                <div className="flex gap-3">
                  <Button
                    variant="outline"
                    className="flex-1"
                    onClick={() => { setGenerateFor(null); setGeneratedCode(null); setGenCodeError('') }}
                  >
                    Cancel
                  </Button>
                  <Button
                    className="flex-1"
                    disabled={genCode.isPending}
                    onClick={() => { setGenCodeError(''); genCode.mutate() }}
                  >
                    {genCode.isPending ? 'Generating…' : 'Generate'}
                  </Button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
