import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, Download, MapPin, MoreHorizontal, Plus, Radio, Trash2 } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Event {
    heard_at: string;
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
    events: Event[];
    created_at: string;
}

interface PaginatedBeacons {
    data: Beacon[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Props {
    beacons: PaginatedBeacons;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Beacons', href: '/beacons' },
];

export default function BeaconsIndex({ beacons }: Props) {
    const handleDelete = (beacon: Beacon) => {
        if (confirm(`Are you sure you want to delete "${beacon.name}"?`)) {
            router.delete(`/beacons/${beacon.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Beacons - EchoTrack" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Beacons</h1>
                        <p className="text-muted-foreground">
                            Manage your campaign and location beacons
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/beacons/create">
                            <Plus className="mr-2 h-4 w-4" />
                            New Beacon
                        </Link>
                    </Button>
                </div>

                {/* Empty State */}
                {beacons.data.length === 0 ? (
                    <Card className="flex flex-col items-center justify-center py-16">
                        <Radio className="h-12 w-12 text-muted-foreground mb-4" />
                        <CardTitle className="mb-2">No beacons yet</CardTitle>
                        <CardDescription className="text-center max-w-md mb-4">
                            Create your first beacon to start tracking ad exposure and store visits.
                            Upload an audio file to generate a watermarked version.
                        </CardDescription>
                        <Button asChild>
                            <Link href="/beacons/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Create Beacon
                            </Link>
                        </Button>
                    </Card>
                ) : (
                    /* Beacon List */
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {beacons.data.map((beacon) => (
                            <Card key={beacon.id} className="relative group">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-3">
                                            <div
                                                className={`flex h-10 w-10 items-center justify-center rounded-full ${beacon.type === 'campaign'
                                                        ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                                        : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                                    }`}
                                            >
                                                {beacon.type === 'campaign' ? (
                                                    <Radio className="h-5 w-5" />
                                                ) : (
                                                    <MapPin className="h-5 w-5" />
                                                )}
                                            </div>
                                            <div>
                                                <CardTitle className="text-base">
                                                    <Link
                                                        href={`/beacons/${beacon.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {beacon.name}
                                                    </Link>
                                                </CardTitle>
                                                <CardDescription className="font-mono text-xs">
                                                    {beacon.beacon_id}
                                                </CardDescription>
                                            </div>
                                        </div>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                >
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/beacons/${beacon.id}`}>
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>
                                                {beacon.watermarked_audio_path && (
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/beacons/${beacon.id}/download`}>
                                                            <Download className="mr-2 h-4 w-4" />
                                                            Download Audio
                                                        </Link>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem
                                                    className="text-destructive"
                                                    onClick={() => handleDelete(beacon)}
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {beacon.description && (
                                        <p className="text-sm text-muted-foreground mb-3 line-clamp-2">
                                            {beacon.description}
                                        </p>
                                    )}
                                    <div className="flex items-center justify-between text-sm">
                                        <div className="flex items-center gap-1 text-muted-foreground">
                                            <Activity className="h-4 w-4" />
                                            <span>{beacon.events_count} events</span>
                                        </div>
                                        <span
                                            className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${beacon.is_active
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                                                }`}
                                        >
                                            {beacon.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {beacons.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {Array.from({ length: beacons.last_page }, (_, i) => (
                            <Button
                                key={i}
                                variant={beacons.current_page === i + 1 ? 'default' : 'outline'}
                                size="sm"
                                asChild
                            >
                                <Link href={`/beacons?page=${i + 1}`}>{i + 1}</Link>
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
