import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import DeleteButton from '@/components/delete-button';
import { Edit2Icon, PlusCircle } from 'lucide-react';
import { BreadcrumbItem, KontrakDokumen, SharedData } from '@/types';
import { toast } from 'sonner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import kontraks from '@/routes/kontraks';
import DeleteButtonChild from '@/components/delete-button-child';


interface Props {
    kontrak_id: number | string
    dokumens: {
        data: KontrakDokumen[];
        links: any[];
    };
    filters: {
        search?: string;
    };
    flash?: {
        success?: string;
    };
}


export default function DokumenPage({ kontrak_id, dokumens, filters, flash }: Props) {

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Kontraks',
            href: kontraks.index.url(),
        },
        {
            title: 'Dokumens',
            href: `/kontraks/${kontrak_id}/dokumens`,
        },
    ];

    const user = usePage<SharedData>().props.auth.user;

    const [search, setSearch] = useState(filters.search || '');
    const [shownMessages] = useState(new Set());

    useEffect(() => {
        if (flash?.success && !shownMessages.has(flash.success)) {
            toast.success(flash.success);
            shownMessages.add(flash.success);
        }
    }, [flash?.success]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(`/kontraks/${kontrak_id}/dokumens`, { search }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dokumens" />

            <div className="p-4 space-y-4">

                {/* Search Bar */}
                <div className='flex space-x-1'>
                    <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-1/3">
                        <Input
                            placeholder="Search dokumens..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button variant='outline' type="submit">Search</Button>
                    </form>
                    {hasAnyPermission(["kontraks dokumens create"]) && (
                        <Link href={`/kontraks/${kontrak_id}/dokumens/create`}>
                            <Button variant='default' className='group flex items-center'>
                                <PlusCircle className='group-hover:rotate-90 transition-all' />
                                Add Dokumens
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Karyawan Table */}
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama Dokumen</TableHead>
                            <TableHead>File</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {dokumens.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="h-[65vh]  text-center">
                                    Belum Ada Data Dokumens.
                                </TableCell>
                            </TableRow>
                        ) : (
                            dokumens.data.map((dokumen) => (
                                <TableRow key={dokumen.id}>
                                    <TableCell>{dokumen.nama_dokumen}</TableCell>
                                    <TableCell>
                                        <a href={`/storage/${dokumen.file}`} target="_blank" rel="noopener noreferrer">
                                            <Button variant='link'>
                                                View File
                                            </Button>
                                        </a>
                                    </TableCell>

                                    <TableCell className="space-x-2">
                                        {hasAnyPermission(["kontraks dokumens edit"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/kontraks/${kontrak_id}/dokumens/${dokumen.id}/edit`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <Edit2Icon /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Edit
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {hasAnyPermission(["karyawans delete"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <DeleteButtonChild id={dokumen.kontrak_id} featured='kontraks' child='dokumens' child_id={dokumen.id} />
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Delete
                                                </TooltipContent>
                                            </Tooltip>
                                        )}
                                    </TableCell>
                                </TableRow>
                            )))}
                    </TableBody>
                </Table>

                <div className="flex gap-1">
                    {dokumens.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`px-3 py-1 flex justify-center items-center border rounded-md ${link.active ? 'bg-black text-white text-sm' : 'text-sm'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>

            </div>
        </AppLayout>
    );
}
