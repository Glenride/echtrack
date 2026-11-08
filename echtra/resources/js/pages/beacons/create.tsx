import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { MapPin, Radio, Upload } from 'lucide-react';
import { FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Beacons', href: '/beacons' },
    { title: 'Create', href: '/beacons/create' },
];

export default function BeaconsCreate() {
    const [fileName, setFileName] = useState<string | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        type: 'campaign' as 'campaign' | 'location',
        description: '',
        audio_file: null as File | null,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/beacons', {
            forceFormData: true,
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('audio_file', file);
        setFileName(file?.name || null);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Beacon - EchoTrack" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Create Beacon</h1>
                    <p className="text-muted-foreground">
                        Create a new beacon to track ad exposure or store visits
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                    {/* Beacon Type */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Beacon Type</CardTitle>
                            <CardDescription>
                                Choose whether this beacon tracks a campaign (ad) or a location (store)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    onClick={() => setData('type', 'campaign')}
                                    className={`flex flex-col items-center gap-3 rounded-lg border-2 p-6 transition-colors ${data.type === 'campaign'
                                            ? 'border-primary bg-primary/5'
                                            : 'border-muted hover:border-muted-foreground/50'
                                        }`}
                                >
                                    <div
                                        className={`flex h-12 w-12 items-center justify-center rounded-full ${data.type === 'campaign'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted'
                                            }`}
                                    >
                                        <Radio className="h-6 w-6" />
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold">Campaign</div>
                                        <div className="text-sm text-muted-foreground">
                                            Track TV/Radio ad exposure
                                        </div>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setData('type', 'location')}
                                    className={`flex flex-col items-center gap-3 rounded-lg border-2 p-6 transition-colors ${data.type === 'location'
                                            ? 'border-primary bg-primary/5'
                                            : 'border-muted hover:border-muted-foreground/50'
                                        }`}
                                >
                                    <div
                                        className={`flex h-12 w-12 items-center justify-center rounded-full ${data.type === 'location'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted'
                                            }`}
                                    >
                                        <MapPin className="h-6 w-6" />
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold">Location</div>
                                        <div className="text-sm text-muted-foreground">
                                            Track in-store visits
                                        </div>
                                    </div>
                                </button>
                            </div>
                            {errors.type && (
                                <p className="mt-2 text-sm text-destructive">{errors.type}</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Basic Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                            <CardDescription>
                                Give your beacon a name and optional description
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., Summer Sale TV Ad or Main Store Entrance"
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Optional description for this beacon..."
                                    className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">{errors.description}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Audio Upload */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Audio Watermarking</CardTitle>
                            <CardDescription>
                                Upload your audio file to embed an ultrasonic watermark (optional)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div
                                    className={`relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 transition-colors ${fileName
                                            ? 'border-primary bg-primary/5'
                                            : 'border-muted hover:border-muted-foreground/50'
                                        }`}
                                >
                                    <input
                                        type="file"
                                        accept=".mp3,.wav"
                                        onChange={handleFileChange}
                                        className="absolute inset-0 cursor-pointer opacity-0"
                                    />
                                    <Upload className="h-10 w-10 text-muted-foreground mb-3" />
                                    {fileName ? (
                                        <div className="text-center">
                                            <p className="font-medium">{fileName}</p>
                                            <p className="text-sm text-muted-foreground">
                                                Click to change file
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="text-center">
                                            <p className="font-medium">Drop audio file here</p>
                                            <p className="text-sm text-muted-foreground">
                                                or click to browse (MP3, WAV up to 50MB)
                                            </p>
                                        </div>
                                    )}
                                </div>
                                {errors.audio_file && (
                                    <p className="text-sm text-destructive">{errors.audio_file}</p>
                                )}
                                <p className="text-sm text-muted-foreground">
                                    The watermarked audio will contain an ultrasonic signal (18-22kHz)
                                    that our SDK can detect. This is inaudible to humans.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Beacon'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/beacons">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
