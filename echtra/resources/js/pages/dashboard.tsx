import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Activity, Radio, MapPin, TrendingUp, Key, Plus, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface BeaconStats {
    total: number;
    campaigns: number;
    locations: number;
}

interface EventStat {
    date: string;
    count: number;
}

interface Beacon {
    id: number;
    beacon_id: string;
    name: string;
    type: 'campaign' | 'location';
    events_count: number;
    created_at: string;
}

interface AiAnalysis {
    analysis: string;
    generated_at: string;
    is_mock: boolean;
    stats: object;
}

interface Props {
    beaconStats: BeaconStats;
    eventStats: EventStat[];
    totalEvents: number;
    attributionStats: EventStat[];
    totalAttributions: number;
    recentBeacons: Beacon[];
    aiAnalysis: AiAnalysis;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard({
    beaconStats,
    eventStats,
    totalEvents,
    attributionStats,
    totalAttributions,
    recentBeacons,
    aiAnalysis,
}: Props) {
    // Calculate max for chart scaling
    const maxEvents = Math.max(...eventStats.map((s) => s.count), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - EchoTrack" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
                        <p className="text-muted-foreground">
                            Ultrasonic Attribution & Retargeting Platform
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/api-keys">
                                <Key className="mr-2 h-4 w-4" />
                                API Keys
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/beacons/create">
                                <Plus className="mr-2 h-4 w-4" />
                                New Beacon
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Beacons</CardTitle>
                            <Radio className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{beaconStats.total}</div>
                            <p className="text-xs text-muted-foreground">
                                {beaconStats.campaigns} campaigns, {beaconStats.locations} locations
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Events</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalEvents.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">Beacon detections</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Attributions</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalAttributions.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">Matched conversions</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Conversion Rate</CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {totalEvents > 0
                                    ? ((totalAttributions / totalEvents) * 100).toFixed(1)
                                    : 0}
                                %
                            </div>
                            <p className="text-xs text-muted-foreground">Campaign → Location</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts and Recent Activity */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                    {/* Events Chart */}
                    <Card className="col-span-4">
                        <CardHeader>
                            <CardTitle>Events (Last 30 Days)</CardTitle>
                            <CardDescription>Beacon detections over time</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[200px] flex items-end gap-1">
                                {eventStats.length === 0 ? (
                                    <div className="flex-1 flex items-center justify-center text-muted-foreground">
                                        No event data yet. Deploy your beacons to start tracking.
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

                    {/* Recent Beacons */}
                    <Card className="col-span-3">
                        <CardHeader>
                            <CardTitle>Recent Beacons</CardTitle>
                            <CardDescription>Your latest beacons</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {recentBeacons.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        No beacons yet.{' '}
                                        <Link href="/beacons/create" className="text-primary underline">
                                            Create your first beacon
                                        </Link>
                                    </p>
                                ) : (
                                    recentBeacons.map((beacon) => (
                                        <Link
                                            key={beacon.id}
                                            href={`/beacons/${beacon.id}`}
                                            className="flex items-center gap-4 hover:bg-muted/50 p-2 -m-2 rounded-lg transition-colors"
                                        >
                                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted">
                                                {beacon.type === 'campaign' ? (
                                                    <Radio className="h-4 w-4" />
                                                ) : (
                                                    <MapPin className="h-4 w-4" />
                                                )}
                                            </div>
                                            <div className="flex-1 space-y-1">
                                                <p className="text-sm font-medium">{beacon.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {beacon.beacon_id} • {beacon.events_count} events
                                                </p>
                                            </div>
                                            <div className="text-xs text-muted-foreground capitalize">
                                                {beacon.type}
                                            </div>
                                        </Link>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* AI Insights */}
                <Card className="border-purple-500/20 bg-gradient-to-br from-purple-500/5 to-transparent">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-purple-500" />
                            <CardTitle>AI Insights</CardTitle>
                            {aiAnalysis?.is_mock && (
                                <span className="text-xs bg-muted px-2 py-0.5 rounded">Demo</span>
                            )}
                        </div>
                        <CardDescription>
                            {aiAnalysis?.generated_at
                                ? `Generated ${new Date(aiAnalysis.generated_at).toLocaleString()}`
                                : 'Attribution analysis'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="prose prose-sm dark:prose-invert max-w-none">
                            {aiAnalysis?.analysis?.split('\n').map((line, i) => (
                                <p key={i} className="mb-2">
                                    {line.replace(/\*\*(.+?)\*\*/g, (_, text) => text)}
                                </p>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
