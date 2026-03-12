import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';

export default function ConfirmationModal({
    show,
    title = 'Confirm Delete',
    message = 'Are you sure you want to delete this item? This action cannot be undone.',
    confirmText = 'Delete',
    cancelText = 'Cancel',
    onCancel,
    onConfirm,
    processing = false,
}) {
    return (
        <Modal show={show} onClose={onCancel} maxWidth="md">
            <div className="p-6">
                <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
                <p className="mt-2 text-sm text-slate-600">{message}</p>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onCancel}>
                        {cancelText}
                    </SecondaryButton>
                    <DangerButton
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {confirmText}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
