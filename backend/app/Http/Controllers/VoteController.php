<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Candidat;
use App\Models\Vote;
use App\Models\VotesUsers;
use App\Models\Score;

class VoteController extends Controller
{
    public function create(Request $request)
{
    try {
        $request->validate([
            'nom' => 'required',
            'description' => 'required',
            'candidats' => 'required|array|min:1',
            'candidats.*.nom' => 'required',
            'candidats.*.description' => 'required',
        ]);

        $vote = new Vote();
        $vote->nom = $request->input('nom');
        $vote->description = $request->input('description');
        $vote->visible = false;
        $vote->save();

        foreach ($request->input('candidats') as $candidatData) {
            $candidat = new Candidat();
            $candidat->nom = $candidatData['nom'];
            $candidat->description = $candidatData['description'];
            $candidat->score = 0;

            if (isset($candidatData['photo_path']) && $candidatData['photo_path']->isFile()) {
                // Vérifier si le fichier est une image
                $imageInfo = getimagesize($candidatData['photo_path']->path());

                if ($imageInfo !== false) {
                    $path = $candidatData['photo_path']->store('images', 'public');
                    $candidat->photo_path = $path;
                } else {
                    // Si ce n'est pas une image, vous pouvez gérer l'erreur ici
                    Log::warning('Le fichier n\'est pas une image.');
                }
            }

            $vote->candidats()->save($candidat);
        }

        Log::info('Vote créé avec succès');

        return response()->json(['message' => 'Vote créé avec succès'], 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la création du vote: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur lors de la création du vote'], 500);
    }
}


    public function index()
    {
        $votes = Vote::all();
        return response()->json($votes, 200);
    }

    public function publish($id)
    {
        try {
            $vote = Vote::findOrFail($id);
            $vote->update(['visible' => true]);

            return response()->json(['message' => 'Vote publié avec succès'], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la publication du vote: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la publication du vote'], 500);
        }
    }

    public function getVotesEnCours()
    {
        try {
            $votesEnCours = Vote::where('visible', 1)->get();
            return response()->json($votesEnCours, 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des votes en cours: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération des votes en cours'], 500);
        }
    }

    public function getCandidatsByVote($voteId)
    {
        try {
            $vote = Vote::find($voteId);
    
            if (!$vote) {
                return response()->json(['error' => 'Vote non trouvé'], 404);
            }
    
            $candidats = $vote->candidats;  // Utilisez la relation définie dans le modèle Vote
    
            return response()->json($candidats, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du chargement des candidats'], 500);
        }
    }    

    public function voteForCandidate(Request $request, $userId, $candidatId)
{
    try {
        $userId = $userId;

        // Vérifier si l'utilisateur a déjà voté pour ce candidat dans ce vote
        $existingVote = VotesUsers::where('user_id', $userId)
                                   ->where('candidate_id', $candidatId)
                                   ->first();

        if ($existingVote) {
            return response()->json(['error' => 'Vous avez déjà voté pour ce candidat dans ce vote.'], 400);
        }
        else{
            // Enregistrer le vote dans la table 'votes_users'
        $vote = new VotesUsers();
        $vote->user_id = $userId;
        $vote->candidate_id = $candidatId;
        $vote->save();

        // Incrémenter le score du candidat
        $candidat = Candidat::find($candidatId);

        if ($candidat) {
            $candidat->score += 1;
            $candidat->save();
        }

        return response()->json(['message' => 'Vote enregistré avec succès.'], 200);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erreur lors du vote.'], 500);
    }
}

public function getVoteResults($voteId)
{
    try {
        // Récupérez les résultats du vote pour chaque candidat
        $results = Candidat::where('vote_id', $voteId)->select('nom', 'score')->get();

        return response()->json(['results' => $results], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erreur lors de la récupération des résultats.'], 500);
    }
}

}
