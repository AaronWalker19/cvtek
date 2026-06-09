import React, { useState } from 'react';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogHeader, AlertDialogTitle } from '../../components/ui/alert-dialog';

interface AdvanceAcademicYearModalProps {
  isOpen: boolean;
  isLoading: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

export default function AdvanceAcademicYearModal({
  isOpen,
  isLoading,
  onConfirm,
  onCancel,
}: AdvanceAcademicYearModalProps) {
  return (
    <AlertDialog open={isOpen}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle className="text-2xl">⚠️ Avancer l'année universitaire</AlertDialogTitle>
          <AlertDialogDescription className="text-base space-y-4 mt-4">
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p className="font-semibold text-yellow-900 mb-3">Cette action va:</p>
              <ul className="space-y-2 text-yellow-800 list-disc list-inside">
                <li>Augmenter d'1 l'année de tous les étudiants</li>
                <li>
                  <strong>Supprimer définitivement</strong> les étudiants en année 4:
                  <ul className="ml-6 mt-1 space-y-1">
                    <li>✗ Compte utilisateur</li>
                    <li>✗ Tous les documents</li>
                    <li>✗ Toutes les versions</li>
                    <li>✗ Tous les commentaires</li>
                    <li>✗ Les fichiers uploads</li>
                  </ul>
                </li>
              </ul>
            </div>

            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
              <p className="font-semibold text-red-900">Cette action est irréversible!</p>
            </div>

            <p className="text-sm font-medium">
              Êtes-vous sûr de vouloir continuer?
            </p>
          </AlertDialogDescription>
        </AlertDialogHeader>

        <div className="flex gap-3 justify-end mt-6">
          <AlertDialogCancel onClick={onCancel} disabled={isLoading}>
            Annuler
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={onConfirm}
            disabled={isLoading}
            className="bg-red-600 hover:bg-red-700"
          >
            {isLoading ? 'Traitement...' : 'Confirmer l\'avancement'}
          </AlertDialogAction>
        </div>
      </AlertDialogContent>
    </AlertDialog>
  );
}
