import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Activity, Copy, Key, Plus, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface ApiKey {
    id: number;
    name: string;
    prefix: string;
    is_active: boolean;
    events_count: number;
    last_used_at: string | null;
    created_at: string;
}

interface Props {
    apiKeys: ApiKey[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'API Keys', href: '/api-keys' },
];

export default function ApiKeysIndex({ apiKeys }: Props) {
    const { flash } = usePage().props as any;
    const [showForm, setShowForm] = useState(false);
    const [copied, setCopied] = useState(false);

    const { data, setData, post, processing, reset } = useForm({
        name: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/api-keys', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    const handleRevoke = (apiKey: ApiKey) => {
        if (confirm(`Are you sure you want to revoke "${apiKey.name}"? This cannot be undone.`)) {
            router.delete(`/api-keys/${apiKey.id}`);
        }
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="API Keys - EchoTrack" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">API Keys</h1>
                        <p className="text-muted-foreground">
                            Manage API keys for SDK authentication
                        </p>
                    </div>
                    <Button onClick={() => setShowForm(!showForm)}>
                        <Plus className="mr-2 h-4 w-4" />
                        New API Key
                    </Button>
                </div>

                {/* New Key Alert */}
                {flash?.newKey && (
                    <Alert className="border-green-500 bg-green-50 dark:bg-green-900/20">
                        <Key className="h-4 w-4 text-green-600" />
                        <AlertTitle className="text-green-800 dark:text-green-200">
                            API Key Created!
                        </AlertTitle>
                        <AlertDescription className="text-green-700 dark:text-green-300">
                            <p className="mb-2">
                                Copy this key now. You won't be able to see it again!
                            </p>
                            <div className="flex items-center gap-2">
                                <code className="flex-1 bg-white dark:bg-black/20 px-3 py-2 rounded font-mono text-sm">
                                    {flash.newKey}
                                </code>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => copyToClipboard(flash.newKey)}
                                >
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            {copied && (
                                <p className="text-xs mt-2">Copied to clipboard!</p>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Create Form */}
                {showForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Create API Key</CardTitle>
                            <CardDescription>
                                Generate a new API key for SDK integration
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="flex items-end gap-4">
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="name">Key Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="e.g., iOS App Production"
                                        required
                                    />
                                </div>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Key'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowForm(false)}
                                >
                                    Cancel
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {/* API Keys List */}
                {apiKeys.length === 0 ? (
                    <Card className="flex flex-col items-center justify-center py-16">
                        <Key className="h-12 w-12 text-muted-foreground mb-4" />
                        <CardTitle className="mb-2">No API keys yet</CardTitle>
                        <CardDescription className="text-center max-w-md mb-4">
                            Create an API key to authenticate your mobile SDK and start
                            receiving beacon events.
                        </CardDescription>
                        <Button onClick={() => setShowForm(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create API Key
                        </Button>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {apiKeys.map((apiKey) => (
                            <Card key={apiKey.id}>
                                <CardContent className="flex items-center justify-between p-6">
                                    <div className="flex items-center gap-4">
                                        <div
                                            className={`flex h-10 w-10 items-center justify-center rounded-full ${apiKey.is_active
                                                    ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                                }`}
                                        >
                                            <Key className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{apiKey.name}</span>
                                                {!apiKey.is_active && (
                                                    <span className="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded">
                                                        Revoked
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-3 text-sm text-muted-foreground">
                                                <code>{apiKey.prefix}...</code>
                                                <span className="flex items-center gap-1">
                                                    <Activity className="h-3 w-3" />
                                                    {apiKey.events_count} events
                                                </span>
                                                {apiKey.last_used_at && (
                                                    <span>
                                                        Last used:{' '}
                                                        {new Date(apiKey.last_used_at).toLocaleDateString()}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    {apiKey.is_active && (
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => handleRevoke(apiKey)}
                                        >
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Revoke
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {/* SDK Integration Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>SDK Integration</CardTitle>
                        <CardDescription>
                            How to use your API key with the EchoTrack SDK
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h4 className="font-medium mb-2">Event Ingestion Endpoint</h4>
                            <code className="block bg-muted p-3 rounded text-sm">
                                POST /api/events
                            </code>
                        </div>
                        <div>
                            <h4 className="font-medium mb-2">Authentication Header</h4>
                            <code className="block bg-muted p-3 rounded text-sm">
                                Authorization: Bearer YOUR_API_KEY
                            </code>
                        </div>
                        <div>
                            <h4 className="font-medium mb-2">Example Request</h4>
                            <pre className="bg-muted p-3 rounded text-sm overflow-x-auto">
                                {`curl -X POST ${window.location.origin}/api/events \\
  -H "Authorization: Bearer eck_xxxxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "beacon_id": "ABC123",
    "device_id": "hashed_device_id",
    "signal_strength_db": -45,
    "timestamp": "2026-01-23T15:00:00Z"
  }'`}
                            </pre>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
