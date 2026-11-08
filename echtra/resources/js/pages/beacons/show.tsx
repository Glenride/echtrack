import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, Download, Edit, MapPin, Radio, Trash2, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface Event {
    id: number;
    device_id: string;
    signal_strength_db: number;
    heard_at: string;
}

interface EventStat {
    date: string;
    count: number;
}

interface Attribution {
    id: number;
    location_beacon: {
        id: number;
        name: string;
        beacon_id: string;
    };
    time_to_conversion_hours: number;
    location_heard_at: string;
}

interface Beacon {
    id: number;
    beacon_id: string;
    name: string;
    type: 'campaign' | 'location';
    description: string | null;
    is_active: boolean;
    events_count: number;
    watermarked_audio_path: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    beacon: Beacon;
    recentEvents: Event[];
    eventStats: EventStat[];
    attributions: Attribution[];
}

export default function BeaconsShow({ beacon, recentEvents, eventStats, attributions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Beacons', href: '/beacons' },
        { title: beacon.name, href: `/beacons/${beacon.id}` },
    ];

    const maxEvents = Math.max(...eventStats.map((s) => s.count), 1);

    const handleDelete = () => {
        if (confirm(`Are you sure you want to delete "${beacon.name}"?`)) {
            router.delete(`/beacons/${beacon.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${beacon.name} - EchoTrack`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-start gap-4">
                        <div
                            className={`flex h-14 w-14 items-center justify-center rounded-full ${beacon.type === 'campaign'
                                    ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                    : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                }`}
                        >
                            {beacon.type === 'campaign' ? (
                                <Radio className="h-7 w-7" />
                            ) : (
                                <MapPin className="h-7 w-7" />
                            )}
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{beacon.name}</h1>
                            <div className="flex items-center gap-3 mt-1">
                                <code className="text-sm text-muted-foreground bg-muted px-2 py-0.5 rounded">
                                    {beacon.beacon_id}
                                </code>
                                <Badge variant={beacon.is_active ? 'default' : 'secondary'}>
                                    {beacon.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                                <span className="text-sm text-muted-foreground capitalize">
                                    {beacon.type}
                                </span>
                            </div>
                            {beacon.description && (
                                <p className="text-muted-foreground mt-2">{beacon.description}</p>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {beacon.watermarked_audio_path && (
                            <Button variant="outline" asChild>
                                <Link href={`/beacons/${beacon.id}/download`}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Download
                                </Link>
                            </Button>
                        )}
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Events</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{beacon.events_count.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">Beacon detections</p>
                        </CardContent>
                    </Card>

                    {beacon.type === 'campaign' && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Attributions</CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{attributions.length}</div>
                                <p className="text-xs text-muted-foreground">Store visits from this ad</p>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Created</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {new Date(beacon.created_at).toLocaleDateString()}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {new Date(beacon.created_at).toLocaleTimeString()}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle>Events (Last 30 Days)</CardTitle>
                        <CardDescription>Beacon detections over time</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[200px] flex items-end gap-1">
                            {eventStats.length === 0 ? (
                                <div className="flex-1 flex items-center justify-center text-muted-foreground">
                                    No event data yet.
                                </div>
                            ) : (
                                eventStats.map((stat, i) => (
                                    <div
                                        key={i}
                                        className="flex-1 bg-primary/80 hover:bg-primary rounded-t transition-colors"
                                        style={{
                                            height: `${(stat.count / maxEvents) * 100}%`,
                                            minHeight: stat.count > 0 ? '4px' : '0px',
                                        }}
                                        title={`${stat.date}: ${stat.count} events`}
                                    />
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Recent Events and Attributions */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Events</CardTitle>
                            <CardDescription>Latest beacon detections</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentEvents.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No events yet.</p>
                            ) : (
                                <div className="space-y-3">
                                    {recentEvents.slice(0, 10).map((event) => (
                                        <div
                                            key={event.id}
                                            className="flex items-center justify-between text-sm"
                                        >
                                            <div>
                                                <code className="text-xs bg-muted px-1 rounded">
                                                    {event.device_id.substring(0, 12)}...
                                                </code>
                                            </div>
                                            <div className="flex items-center gap-2 text-muted-foreground">
                                                <span>{event.signal_strength_db}dB</span>
                                                <span>
                                                    {new Date(event.heard_at).toLocaleString()}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {beacon.type === 'campaign' && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Attributions</CardTitle>
                                <CardDescription>Store visits from this campaign</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {attributions.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        No attributions yet. Users who hear this campaign
                                        and then visit a tracked location will appear here.
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {attributions.slice(0, 10).map((attr) => (
                                            <div
                                                key={attr.id}
                                                className="flex items-center justify-between text-sm"
                                            >
                                                <Link
                                                    href={`/beacons/${attr.location_beacon.id}`}
                                                    className="hover:underline"
                                                >
                                                    {attr.location_beacon.name}
                                                </Link>
                                                <div className="text-muted-foreground">
                                                    {attr.time_to_conversion_hours}h →{' '}
                                                    {new Date(attr.location_heard_at).toLocaleDateString()}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
